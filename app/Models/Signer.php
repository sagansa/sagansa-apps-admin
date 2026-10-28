<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Penandatangan surat internal (tabel `signers` di database `sagansa_document`,
 * koneksi `mysql_document`).
 *
 * CATATAN: model ini SENGAJA tidak memiliki relasi Eloquent ke App\Models\User.
 * `User` hidup di koneksi `mysql_auth` (database `sagansa_user`) sedangkan
 * `Signer` di `mysql_document` — relasi lintas koneksi tidak didukung konsisten
 * oleh Eloquent (join/eager-load mengacu koneksi model induk). Resource Filament
 * memakai lookup manual (map id => nama) sebagai gantinya.
 *
 * `user_id` merujuk `users.id` di `sagansa_user` tanpa FK lintas database
 * (mengikuti pola services/migration). Unique constraint DB: (user_id, role).
 */
class Signer extends Model
{
    /**
     * Koneksi database sagansa_document.
     */
    protected $connection = 'mysql_document';

    protected $table = 'signers';

    protected $fillable = [
        'user_id',
        'role',
        'display_name',
        'title',
        'signature_asset',
        'is_active',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }
}
