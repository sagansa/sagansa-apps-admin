<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ServiceSetting extends Model
{
    protected $connection = 'mysql';

    protected $guarded = [];

    public static function getValue(string $key, ?string $default = null): ?string
    {
        $setting = static::where('key', $key)->first();
        return $setting ? $setting->value : $default;
    }

    public static function setValue(string $key, ?string $value): void
    {
        static::updateOrCreate(
            ['key' => $key],
            ['value' => $value, 'description' => static::getDefaultDescription($key)]
        );
    }

    protected static function getDefaultDescription(string $key): ?string
    {
        return match ($key) {
            'force_mobile_only' => 'Saat aktif, hanya admin & super-admin yang bisa akses panel admin. Nonaktifkan untuk darurat.',
            default => null,
        };
    }
}
