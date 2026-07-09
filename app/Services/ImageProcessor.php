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
            $jpegPath = $this->convertHeicWithImagick($path);
        }

        if (! $jpegPath) {
            $jpegPath = $this->convertHeicWithGd($path);
        }

        if (! $jpegPath) {
            $jpegPath = $this->extractHeicJpegItem($path);
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

    private function convertHeicWithImagick(string $path): ?string
    {
        if (! extension_loaded('imagick') || ! class_exists(\Imagick::class)) {
            return null;
        }

        try {
            $imagick = new \Imagick();
            $imagick->readImage($path);

            $format = $imagick->getImageFormat();
            if (! in_array(strtoupper($format), ['HEIC', 'HEIF', 'AVIF'], true)) {
                $imagick->destroy();

                return null;
            }

            $imagick->setImageFormat('jpeg');
            $imagick->setImageCompressionQuality(90);

            $outputPath = tempnam(sys_get_temp_dir(), 'heic_imagick_') . '.jpg';
            $imagick->writeImage($outputPath);
            $imagick->destroy();

            if (file_exists($outputPath) && filesize($outputPath) > 0) {
                return $outputPath;
            }
        } catch (\Throwable) {
            // Fall through
        }

        return null;
    }

    private function convertHeicWithGd(string $path): ?string
    {
        if (! function_exists('imagecreatefromstring')) {
            return null;
        }

        try {
            $data = file_get_contents($path);

            if ($data === false || strlen($data) < 20) {
                return null;
            }

            $image = @imagecreatefromstring($data);

            if ($image === false) {
                return null;
            }

            $outputPath = tempnam(sys_get_temp_dir(), 'heic_gd_') . '.jpg';
            imagejpeg($image, $outputPath, 90);
            imagedestroy($image);

            if (file_exists($outputPath) && filesize($outputPath) > 0) {
                return $outputPath;
            }
        } catch (\Throwable) {
            // Fall through
        }

        return null;
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

    private function extractHeicJpegItem(string $path): ?string
    {
        $data = file_get_contents($path);

        if ($data === false || strlen($data) < 20) {
            return null;
        }

        $boxes = $this->parseISOBmffBoxes($data, 0, strlen($data));

        $metaBox = null;

        foreach ($boxes as $box) {
            if ($box['type'] === 'meta') {
                $metaBox = $box;

                break;
            }
        }

        if (! $metaBox) {
            return null;
        }

        $metaBody = substr($data, $metaBox['bodyOffset'], $metaBox['bodySize']);
        $metaBoxes = $this->parseISOBmffBoxes($data, $metaBox['bodyOffset'] + 4, $metaBox['bodySize'] - 4);

        $iloc = null;
        $iinf = null;
        $iref = null;

        foreach ($metaBoxes as $box) {
            if ($box['type'] === 'iloc') {
                $iloc = $box;
            } elseif ($box['type'] === 'iinf') {
                $iinf = $box;
            } elseif ($box['type'] === 'iref') {
                $iref = $box;
            }
        }

        if (! $iloc || ! $iinf) {
            return null;
        }

        $items = $this->parseIinfItems($data, $iinf);

        $jpegItemId = null;

        foreach ($items as $id => $type) {
            if (in_array($type, ['jpeg', 'jpg '], true)) {
                $jpegItemId = $id;

                break;
            }
        }

        $thumbItemIds = [];

        if ($iref && $jpegItemId === null) {
            $thumbItemIds = $this->parseIrefThumbnails($data, $iref);

            foreach ($thumbItemIds as $id) {
                if (isset($items[$id])) {
                    $jpegItemId = $id;

                    break;
                }
            }
        }

        $locations = $this->parseIlocLocations($data, $iloc);

        if ($jpegItemId !== null && isset($locations[$jpegItemId])) {
            $loc = $locations[$jpegItemId];

            if (isset($loc['extents']) && count($loc['extents']) > 0) {
                $jpegData = '';

                foreach ($loc['extents'] as $extent) {
                    $jpegData .= substr($data, $extent['offset'], $extent['length']);
                }

                $outputPath = tempnam(sys_get_temp_dir(), 'heic_item_') . '.jpg';
                file_put_contents($outputPath, $jpegData);

                if (@imagecreatefromjpeg($outputPath) !== false) {
                    return $outputPath;
                }

                unlink($outputPath);
            }
        }

        // Try extracting JPEG thumbnail from Exif item data
        $exifJpeg = $this->extractJpegFromExifItem($data, $items, $locations);

        if ($exifJpeg) {
            return $exifJpeg;
        }

        // Try naive JPEG marker extraction as last resort
        return $this->extractEmbeddedJpeg($data);
    }

    private function parseISOBmffBoxes(string $data, int $start, int $length): array
    {
        $boxes = [];
        $offset = $start;
        $end = $start + $length;

        while ($offset + 8 <= $end) {
            $boxSize = unpack('N', substr($data, $offset, 4))[1];
            $boxType = substr($data, $offset + 4, 4);
            $headerSize = 8;

            if ($boxSize === 1 && $offset + 12 <= $end) {
                $boxSize = unpack('J', substr($data, $offset + 8, 8))[1];
                $headerSize = 16;
            } elseif ($boxSize === 0) {
                $boxSize = $end - $offset;
            }

            $bodyOffset = $offset + $headerSize;
            $bodySize = $boxSize - $headerSize;

            $boxes[] = [
                'type' => $boxType,
                'offset' => $offset,
                'size' => $boxSize,
                'bodyOffset' => $bodyOffset,
                'bodySize' => $bodySize,
            ];

            $offset += $boxSize;
        }

        return $boxes;
    }

    private function parseIinfItems(string $data, array $iinf): array
    {
        $items = [];
        $bodyData = substr($data, $iinf['bodyOffset'], $iinf['bodySize']);

        if (strlen($bodyData) < 6) {
            return $items;
        }

        // Read entry_count at byte 4 (after 4-byte FullBox header)
        $entryCount = unpack('n', substr($bodyData, 4, 2))[1];

        // After entry_count (byte 6), entries can be either:
        // 1. 'infe' sub-boxes (each with own size+type header) [modern spec]
        // 2. Plain records [older spec]
        // Detect by checking if first bytes look like a box (type 'infe')
        $pos = 6;

        if ($pos + 8 <= strlen($bodyData) && substr($bodyData, $pos + 4, 4) === 'infe') {
            // Format 1: 'infe' sub-boxes
            for ($i = 0; $i < $entryCount; $i++) {
                if ($pos + 8 > strlen($bodyData)) {
                    break;
                }

                $boxSize = unpack('N', substr($bodyData, $pos, 4))[1];
                $boxType = substr($bodyData, $pos + 4, 4);

                if ($boxType !== 'infe' || $pos + $boxSize > strlen($bodyData)) {
                    break;
                }

                $itemId = null;
                $itemType = null;
                $infeBody = substr($bodyData, $pos + 8, $boxSize - 8);

                if (strlen($infeBody) < 4) {
                    $pos += $boxSize;
                    continue;
                }

                $infeVersion = ord($infeBody[0]);

                if ($infeVersion >= 2) {
                    // ItemInfoEntry version >= 2 extends FullBox
                    // version+flags (4 bytes), then item_ID (4 bytes for v2+)
                    // Actually in ISO 23008-12, for version >= 2, item_ID is 32 bits
                    // But the body starts with version at byte 0
                    // For v2: item_ID is at byte 4 (after version+flags)
                    $itemId = unpack('N', substr($infeBody, 4, 4))[1];
                    $itemType = trim(substr($infeBody, 10, 4));
                } else {
                    // Version 0 or 1: simple box (no version+flags in body)
                    // item_ID at byte 0 (16 bits)
                    $itemId = unpack('n', substr($infeBody, 0, 2))[1];
                    $itemType = trim(substr($infeBody, 4, 4));
                }

                if ($itemId !== null && $itemType !== null) {
                    $items[$itemId] = $itemType;
                }

                $pos += $boxSize;
            }
        } else {
            // Format 2: plain records
            for ($i = 0; $i < $entryCount; $i++) {
                if ($pos + 8 > strlen($bodyData)) {
                    break;
                }

                $itemId = unpack('n', substr($bodyData, $pos, 2))[1];
                $itemType = trim(substr($bodyData, $pos + 4, 4));
                $items[$itemId] = $itemType;

                $pos += 8;
            }
        }

        return $items;
    }

    private function parseIlocLocations(string $data, array $iloc): array
    {
        $locations = [];
        $bodyData = substr($data, $iloc['bodyOffset'], $iloc['bodySize']);

        if (strlen($bodyData) < 8) {
            return $locations;
        }

        $version = ord($bodyData[0]);

        // 2-byte iloc header at bytes 4-5: offset_size(4) + length_size(4) + base_offset_size(4) + index_size(4)
        $b = unpack('n', substr($bodyData, 4, 2))[1];
        $offsetSize = ($b >> 12) & 0xF;
        $lengthSize = ($b >> 8) & 0xF;
        $baseOffsetSize = ($b >> 4) & 0xF;
        $indexSize = $version >= 2 ? ($b & 0xF) : 0;

        // item_count starts at byte 6 (after 4-byte FullBox header + 2-byte iloc header)
        // version < 2: uint16, version >= 2: uint32
        $itemCountOffset = 6;
        $itemCountSize = $version < 2 ? 2 : 4;

        if (strlen($bodyData) < $itemCountOffset + $itemCountSize) {
            return $locations;
        }

        $itemCount = $version < 2
            ? unpack('n', substr($bodyData, $itemCountOffset, 2))[1]
            : unpack('N', substr($bodyData, $itemCountOffset, 4))[1];

        $pos = $itemCountOffset + $itemCountSize;

        $bodyLen = strlen($bodyData);

        for ($i = 0; $i < $itemCount; $i++) {
            $itemIdSize = $version < 2 ? 2 : 4;

            if ($pos + $itemIdSize > $bodyLen) {
                break;
            }

            $itemID = $version < 2
                ? unpack('n', substr($bodyData, $pos, 2))[1]
                : unpack('N', substr($bodyData, $pos, 4))[1];
            $pos += $itemIdSize;

            // construction_method (2 bits) + reserved (14 bits) = 2 bytes
            // for version 0: data_reference_index instead
            if ($pos + 2 > $bodyLen) {
                break;
            }
            $pos += 2;

            $baseOffset = 0;

            if ($baseOffsetSize > 0 && $pos + $baseOffsetSize <= $bodyLen) {
                $baseOffset = $this->readUint($bodyData, $pos, $baseOffsetSize);
                $pos += $baseOffsetSize;
            }

            // extent_count is always present (2 bytes) for all versions
            $extentCount = 0;
            if ($pos + 2 <= $bodyLen) {
                $extentCount = unpack('n', substr($bodyData, $pos, 2))[1];
                $pos += 2;
            }

            $extents = [];

            for ($j = 0; $j < $extentCount; $j++) {
                if ($indexSize > 0) {
                    if ($pos + $indexSize > $bodyLen) {
                        break;
                    }
                    $pos += $indexSize;
                }

                $extentOffset = 0;
                if ($pos + $offsetSize <= $bodyLen) {
                    $extentOffset = $this->readUint($bodyData, $pos, $offsetSize);
                    $pos += $offsetSize;
                }

                $extentLength = 0;
                if ($pos + $lengthSize <= $bodyLen) {
                    $extentLength = $this->readUint($bodyData, $pos, $lengthSize);
                    $pos += $lengthSize;
                }

                $extents[] = [
                    'offset' => $baseOffset + $extentOffset,
                    'length' => $extentLength,
                ];
            }

            $locations[$itemID] = [
                'extents' => $extents,
            ];
        }

        return $locations;
    }

    private function parseIrefThumbnails(string $data, array $iref): array
    {
        $thumbIds = [];

        $refBoxes = $this->parseISOBmffBoxes($data, $iref['bodyOffset'] + 4, $iref['bodySize'] - 4);

        foreach ($refBoxes as $box) {
            if ($box['type'] === 'thmb') {
                $boxData = substr($data, $box['bodyOffset'], $box['bodySize']);

                if (strlen($boxData) < 12) {
                    continue;
                }

                $fromId = unpack('n', substr($boxData, 4, 2))[1];
                $refCount = unpack('n', substr($boxData, 6, 2))[1];

                for ($i = 0; $i < $refCount; $i++) {
                    $toIdOffset = 8 + ($i * 2);
                    if ($toIdOffset + 2 <= strlen($boxData)) {
                        $thumbIds[] = unpack('n', substr($boxData, $toIdOffset, 2))[1];
                    }
                }
            }
        }

        return $thumbIds;
    }

    private function readUint(string $data, int $offset, int $bytes): int
    {
        $value = 0;

        for ($i = 0; $i < $bytes; $i++) {
            if ($offset + $i >= strlen($data)) {
                break;
            }

            $value = ($value << 8) | ord($data[$offset + $i]);
        }

        return $value;
    }

    private function extractEmbeddedJpeg(string $data): ?string
    {
        $jpegs = [];
        $offset = 0;

        while (($start = strpos($data, "\xFF\xD8\xFF", $offset)) !== false) {
            $end = strpos($data, "\xFF\xD9", $start);

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
        $jpegData = substr($data, $best['start'], $best['length']);

        $outputPath = tempnam(sys_get_temp_dir(), 'heic_embedded_') . '.jpg';
        file_put_contents($outputPath, $jpegData);

        if (@imagecreatefromjpeg($outputPath) === false) {
            unlink($outputPath);

            return null;
        }

        return $outputPath;
    }

    private function extractJpegFromExifItem(string $data, array $items, array $locations): ?string
    {
        $exifItemId = null;

        foreach ($items as $id => $type) {
            if (in_array($type, ['Exif'], true)) {
                $exifItemId = $id;
                break;
            }
        }

        if ($exifItemId === null || ! isset($locations[$exifItemId])) {
            return null;
        }

        $loc = $locations[$exifItemId];

        if (! isset($loc['extents']) || count($loc['extents']) === 0) {
            return null;
        }

        $exifData = '';

        foreach ($loc['extents'] as $extent) {
            $exifData .= substr($data, $extent['offset'], $extent['length']);
        }

        if (strlen($exifData) < 12) {
            return null;
        }

        return $this->extractThumbnailFromExif($exifData);
    }

    private function extractThumbnailFromExif(string $exifData): ?string
    {
        // TIFF header
        $byteOrder = substr($exifData, 0, 2);
        $isLittleEndian = $byteOrder === 'II';

        $read16 = function (int $offset) use ($exifData, $isLittleEndian): int {
            if ($offset + 2 > strlen($exifData)) {
                return 0;
            }

            $bytes = substr($exifData, $offset, 2);

            return $isLittleEndian
                ? unpack('v', $bytes)[1]
                : unpack('n', $bytes)[1];
        };

        $read32 = function (int $offset) use ($exifData, $isLittleEndian): int {
            if ($offset + 4 > strlen($exifData)) {
                return 0;
            }

            $bytes = substr($exifData, $offset, 4);

            return $isLittleEndian
                ? unpack('V', $bytes)[1]
                : unpack('N', $bytes)[1];
        };

        if (! in_array($byteOrder, ['II', 'MM'], true)) {
            return null;
        }

        // Magic: 0x002A
        if ($read16(2) !== 0x002A) {
            return null;
        }

        // Offset to IFD0
        $ifd0Offset = $read32(4);

        if ($ifd0Offset < 8 || $ifd0Offset + 2 > strlen($exifData)) {
            return null;
        }

        // Read IFD0 entry count
        $ifd0Count = $read16($ifd0Offset);

        // IFD0 entries start after the count
        $ifd0EntriesEnd = $ifd0Offset + 2 + ($ifd0Count * 12);

        if ($ifd0EntriesEnd + 4 > strlen($exifData)) {
            return null;
        }

        // Next IFD offset (IFD1) is right after IFD0 entries
        $ifd1Offset = $read32($ifd0EntriesEnd);

        if ($ifd1Offset < 8 || $ifd1Offset + 2 > strlen($exifData)) {
            return null;
        }

        // Read IFD1 entry count
        $ifd1Count = $read16($ifd1Offset);

        $jpegOffset = null;
        $jpegLength = null;

        for ($i = 0; $i < $ifd1Count; $i++) {
            $entryOffset = $ifd1Offset + 2 + ($i * 12);

            if ($entryOffset + 12 > strlen($exifData)) {
                break;
            }

            $tag = $read16($entryOffset);
            $type = $read16($entryOffset + 2);
            $count = $read32($entryOffset + 4);
            $value = $read32($entryOffset + 8);

            // Tag: JPEGInterchangeFormat (513 = 0x0201)
            if ($tag === 0x0201) {
                $jpegOffset = $value;
            }

            // Tag: JPEGInterchangeFormatLength (514 = 0x0202)
            if ($tag === 0x0202) {
                $jpegLength = $value;
            }
        }

        if ($jpegOffset === null || $jpegLength === null || $jpegLength <= 0) {
            return null;
        }

        if ($jpegOffset + $jpegLength > strlen($exifData)) {
            return null;
        }

        $jpegData = substr($exifData, $jpegOffset, $jpegLength);

        $outputPath = tempnam(sys_get_temp_dir(), 'heic_exif_') . '.jpg';
        file_put_contents($outputPath, $jpegData);

        if (@imagecreatefromjpeg($outputPath) === false) {
            unlink($outputPath);

            return null;
        }

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
