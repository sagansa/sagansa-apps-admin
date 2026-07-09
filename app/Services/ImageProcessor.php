<?php

namespace App\Services;

use Maestroerror\HeicToJpg;
use Illuminate\Http\UploadedFile;

class ImageProcessor
{
    private const MAX_DIMENSION = 1920;

    private const WEBP_QUALITY = 80;

    private const WATERMARK_TEXT = 'www.sagansa.id';

    private const WATERMARK_OPACITY = 50;

    /**
     * Process uploaded image: convert HEIC, resize, watermark, output WebP.
     *
     * @return string Absolute path to processed WebP file (caller must clean up).
     */
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
            $converter = HeicToJpg::convert($path);
            $jpegPath = tempnam(sys_get_temp_dir(), 'heic_') . '.jpg';
            $converter->saveAs($jpegPath);
            $image = imagecreatefromjpeg($jpegPath);
            unlink($jpegPath);

            return $image;
        }

        return match ($ext) {
            'png' => imagecreatefrompng($path),
            'gif' => imagecreatefromgif($path),
            'webp' => imagecreatefromwebp($path),
            'bmp' => imagecreatefrombmp($path),
            default => imagecreatefromjpeg($path),
        };
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
