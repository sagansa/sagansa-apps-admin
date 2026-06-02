<?php

namespace App\Support;

use Illuminate\Support\Facades\Storage;

class PublicStorageUrl
{
    public static function from(?string $value): ?string
    {
        if (!$value) {
            return null;
        }

        $value = trim($value);

        if ($value === '') {
            return null;
        }

        if (filter_var($value, FILTER_VALIDATE_URL)) {
            $host = parse_url($value, PHP_URL_HOST);

            if ($host && !in_array($host, ['localhost', '127.0.0.1'], true)) {
                return $value;
            }

            $path = parse_url($value, PHP_URL_PATH) ?: '';
            return self::fromPath($path);
        }

        return self::fromPath($value);
    }

    private static function fromPath(string $path): ?string
    {
        $path = ltrim($path, '/');

        if (str_starts_with($path, 'storage/')) {
            $path = substr($path, strlen('storage/'));
        }

        return $path === '' ? null : Storage::disk('public')->url($path);
    }
}
