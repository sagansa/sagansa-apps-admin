<?php

namespace App\Models;

use App\Support\PublicStorageUrl;
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
        'signature_asset_path',
        'signature_asset',
        'is_active',
    ];

    /**
     * Aset base64 (≈1,4 MB untuk PNG 1 MB) TIDAK boleh ikut
     * `attributesToArray()` — Filament default mengirim seluruh atribut
     * record ke browser lewat snapshot Livewire, lalu dikirim balik di
     * SETIAP POST ke endpoint update Livewire. Body melebihi
     * `post_max_size` → PHP membuang body → `_token` hilang → respons 419.
     *
     * `$hidden` hanya menyaring serialisasi; akses atribut langsung
     * (`$signer->signature_asset`) dan penulisan mass-assign tetap jalan.
     * EditSigner::mutateFormDataBeforeFill() mengulang pengaman ini secara
     * eksplisit agar tetap aman bila `$hidden` suatu saat dilepas.
     */
    protected $hidden = ['signature_asset'];

    private const PNG_MAGIC = "\x89PNG\r\n\x1a\n";

    /**
     * URL pratinjau aset tanda tangan: path img service bila ada, jika tidak
     * base64 legacy sebagai data URI.
     */
    public function signatureUrl(): ?string
    {
        if (filled($this->signature_asset_path)) {
            return PublicStorageUrl::from($this->signature_asset_path);
        }

        return $this->signatureDataUri();
    }

    /**
     * Data URI PNG untuk pratinjau di form edit (null bila kosong/korup).
     * Hanya untuk aset legacy base64 — aset baru sudah berupa URL img service.
     */
    public function signatureDataUri(): ?string
    {
        $asset = $this->signature_asset;

        if (! is_string($asset) || trim($asset) === '') {
            return null;
        }

        $binary = base64_decode($asset, true);

        if ($binary === false || ! str_starts_with($binary, self::PNG_MAGIC)) {
            return null;
        }

        return 'data:image/png;base64,'.base64_encode($binary);
    }

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
