<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;
use RuntimeException;

class ApiKey extends Model
{
    use HasUuids;

    protected $connection = 'mysql_auth';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'scopes' => 'array',
            'allowed_ips' => 'array',
            'rate_limit' => 'integer',
            'last_used_at' => 'datetime',
            'expires_at' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(ApiKeyAuditLog::class);
    }

    public function newUniqueId(): string
    {
        return (string) Str::orderedUuid();
    }

    /** Format: sga_sk_ + 43 karakter base62 (total 50). */
    public static function generatePlainKey(): string
    {
        return 'sga_sk_'.Str::random(43);
    }

    /**
     * Generate key yang hash-nya belum ada di DB (maks 5 percobaan).
     * Kolisi SHA-256 nyaris mustahil; guard ini untuk kepastian.
     */
    public static function generateUniquePlainKey(): string
    {
        for ($attempt = 0; $attempt < 5; $attempt++) {
            $plainKey = static::generatePlainKey();

            if (! static::where('key_hash', static::hashKey($plainKey))->exists()) {
                return $plainKey;
            }
        }

        throw new RuntimeException('Gagal membuat API key unik, coba lagi.');
    }

    public static function hashKey(string $plainKey): string
    {
        return hash('sha256', $plainKey);
    }

    /** 10 karakter pertama + elipsis, mis. sga_sk_Ab3dE… */
    public static function prefixOf(string $plainKey): string
    {
        return substr($plainKey, 0, 10).'…';
    }

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    /** Status: Kedaluwarsa | Nonaktif | Aktif. */
    public function status(): string
    {
        if ($this->isExpired()) {
            return 'Kedaluwarsa';
        }

        return $this->is_active ? 'Aktif' : 'Nonaktif';
    }
}
