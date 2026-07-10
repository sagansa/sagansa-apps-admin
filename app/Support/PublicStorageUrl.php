<?php

namespace App\Support;

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

        if ($path === '') {
            return null;
        }

        // Redirect mobile presence/readiness uploads to the API media server domain
        if (str_contains($path, 'presences/') || str_contains($path, 'readinesses/')) {
            $apiUrl = rtrim(env('API_URL', 'https://api.sagansa.id'), '/');

            return $apiUrl . '/media/' . $path;
        }

        // Gambar lain disajikan dari image service (img.sagansa.id).
        // File lama (yang sudah disalin ke image service) tetap pakai path yang sama.
        $imgServiceUrl = rtrim((string) env('IMG_SERVICE_URL', 'https://img.sagansa.id'), '/');

        return $imgServiceUrl . '/storage/' . $path;
    }
}
