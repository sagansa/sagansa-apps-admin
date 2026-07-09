<?php

namespace App\Services;

use Maestroerror\HeicToJpg;
use Illuminate\Http\UploadedFile;
use Symfony\Component\Process\Process;

class ImageProcessor
{
    private const MAX_DIMENSION = 1920;

    private const WEBP_QUALITY = 80;

    private const WATERMARK_TEXT = 'www.sagansa.id';

    private const WATERMARK_OPACITY = 50;

    public function process(UploadedFile $file): string
    {
        $inputPath = $file->getRealPath();
        $ext = strtolower($file->getClientOriginalExtension());

        $image = $this->loadImage($inputPath, $ext);
        $image = $this->resizeToWebFriendly($image);
        $this->applyWatermark($image);

        $outputPath = tempnam(sys_get_temp_dir(), 'img_') . '.webp';
        imagewebp($image, $outputPath, self::WEBP_QUALITY);

        return $outputPath;
    }

    private function loadImage(string $path, string $ext): \GdImage
    {
        if (in_array($ext, ['heic', 'heif'], true)) {
            return $this->convertHeic($path);
        }

        return match ($ext) {
            'png' => imagecreatefrompng($path),
            'gif' => imagecreatefromgif($path),
            'webp' => imagecreatefromwebp($path),
            'bmp' => imagecreatefrombmp($path),
            default => imagecreatefromjpeg($path),
        };
    }

    private function convertHeic(string $path): \GdImage
    {
        $jpegPath = null;

        if (function_exists('proc_open')) {
            try {
                $jpegPath = $this->convertHeicWithBinary($path);
            } catch (\Throwable) {
                $jpegPath = null;
            }
        }

        if (! $jpegPath && class_exists(HeicToJpg::class) && function_exists('exec')) {
            try {
                $converter = HeicToJpg::convert($path);
                $jpegPath = tempnam(sys_get_temp_dir(), 'heic_') . '.jpg';
                $converter->saveAs($jpegPath);
            } catch (\Throwable) {
                $jpegPath = null;
            }
        }

        if (! $jpegPath) {
            $jpegPath = $this->extractEmbeddedJpeg($path);
        }

        if (! $jpegPath) {
            throw new \RuntimeException(
                'Could not convert HEIC image. Please upload JPEG, PNG, or WebP instead.'
            );
        }

        $image = imagecreatefromjpeg($jpegPath);
        unlink($jpegPath);

        return $image;
    }

    private function convertHeicWithBinary(string $path): string
    {
        $binary = $this->findHeicBinary();

        if (! $binary) {
            throw new \RuntimeException('No HEIC converter binary found for this platform');
        }

        $outputPath = tempnam(sys_get_temp_dir(), 'heic_bin_') . '.jpg';

        $process = new Process([$binary, $path, $outputPath]);
        $process->run();

        if (! $process->isSuccessful() || ! file_exists($outputPath) || filesize($outputPath) === 0) {
            if (file_exists($outputPath)) {
                unlink($outputPath);
            }

            throw new \RuntimeException('HEIC binary conversion failed: ' . $process->getErrorOutput());
        }

        return $outputPath;
    }

    private function findHeicBinary(): ?string
    {
        $baseDir = dirname(__DIR__, 2) . '/vendor/maestroerror/php-heic-to-jpg/bin';
        $os = PHP_OS_FAMILY;
        $arch = strtolower(php_uname('m'));

        $binary = match (true) {
            $os === 'Darwin' && in_array($arch, ['arm64', 'aarch64']) => 'php-heic-to-jpg-darwin-arm64',
            $os === 'Darwin' => 'php-heic-to-jpg-darwin-amd64',
            $os === 'Linux' && in_array($arch, ['arm64', 'aarch64']) => 'php-heic-to-jpg-linux-arm64',
            $os === 'Linux' => 'heicToJpg',
            $os === 'Windows' => 'heicToJpg.exe',
            default => null,
        };

        if (! $binary) {
            return null;
        }

        $path = $baseDir . '/' . $binary;

        return file_exists($path) ? $path : null;
    }

    private function extractEmbeddedJpeg(string $heicPath): ?string
    {
        $size = @filesize($heicPath);

        if (! $size) {
            return null;
        }

        $handle = fopen($heicPath, 'rb');

        if (! $handle) {
            return null;
        }

        $contents = fread($handle, $size);
        fclose($handle);

        $jpegs = [];
        $offset = 0;

        while (($start = strpos($contents, "\xFF\xD8\xFF", $offset)) !== false) {
            $end = strpos($contents, "\xFF\xD9", $start);

            if ($end === false) {
                break;
            }

            $length = $end - $start + 2;

            if ($length > 1000) {
                $jpegs[] = ['start' => $start, 'length' => $length];
            }

            $offset = $start + 1;
        }

        if (empty($jpegs)) {
            return null;
        }

        usort($jpegs, fn (array $a, array $b): int => $b['length'] <=> $a['length']);

        $best = $jpegs[0];
        $jpegData = substr($contents, $best['start'], $best['length']);

        $outputPath = tempnam(sys_get_temp_dir(), 'heic_embedded_') . '.jpg';
        file_put_contents($outputPath, $jpegData);

        $image = @imagecreatefromjpeg($outputPath);

        if ($image === false) {
            unlink($outputPath);

            return null;
        }

        imagedestroy($image);

        return $outputPath;
    }

    private function resizeToWebFriendly(\GdImage $image): \GdImage
    {
        $w = imagesx($image);
        $h = imagesy($image);

        if ($w <= self::MAX_DIMENSION && $h <= self::MAX_DIMENSION) {
            return $image;
        }

        $ratio = min(self::MAX_DIMENSION / $w, self::MAX_DIMENSION / $h);
        $newW = (int) round($w * $ratio);
        $newH = (int) round($h * $ratio);

        $resized = imagescale($image, $newW, $newH);

        return $resized;
    }

    private function applyWatermark(\GdImage $image): void
    {
        $w = imagesx($image);
        $h = imagesy($image);

        $overlay = imagecreatetruecolor($w, $h);
        imagesavealpha($overlay, true);
        $bg = imagecolorallocatealpha($overlay, 0, 0, 0, 127);
        imagefill($overlay, 0, 0, $bg);

        $fontFile = $this->findFont();
        $fontSize = max(18, min(48, $w / 22));
        $color = imagecolorallocatealpha($overlay, 255, 255, 255, 60);

        if ($fontFile) {
            $bbox = imagettfbbox($fontSize, 0, $fontFile, self::WATERMARK_TEXT);
            $tw = $bbox[2] - $bbox[0];
            $th = $bbox[1] - $bbox[7];
            $x = (int) (($w - $tw) / 2);
            $y = (int) (($h - $th) / 2 + $th);
            imagettftext($overlay, $fontSize, 0, $x, $y, $color, $fontFile, self::WATERMARK_TEXT);
        } else {
            $tw = imagefontwidth(5) * strlen(self::WATERMARK_TEXT);
            $th = imagefontheight(5);
            $x = (int) (($w - $tw) / 2);
            $y = (int) (($h - $th) / 2);
            imagestring($overlay, 5, $x, $y, self::WATERMARK_TEXT, $color);
        }

        imagecopymerge($image, $overlay, 0, 0, 0, 0, $w, $h, self::WATERMARK_OPACITY);
    }

    private function findFont(): ?string
    {
        $bundled = __DIR__ . '/../../resources/fonts/Inter-Regular.ttf';

        if (file_exists($bundled)) {
            return $bundled;
        }

        foreach ([
            '/System/Library/Fonts/Helvetica.ttc',
            '/System/Library/Fonts/Supplemental/Arial.ttf',
            '/usr/share/fonts/truetype/dejavu/DejaVuSans.ttf',
            '/usr/share/fonts/TTF/DejaVuSans.ttf',
            'C:\\Windows\\Fonts\\arial.ttf',
        ] as $candidate) {
            if (file_exists($candidate)) {
                return $candidate;
            }
        }

        return null;
    }
}
