<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Kategori aset — menentukan frekuensi pemeriksaan (frequency_days) dan
 * checklist baku (checklist_definition). Dipakai oleh modul Manajemen Aset
 * dan oleh ProductResource (admin) saat menandai produk sebagai aset.
 */
class AssetCategory extends Model
{
    use HasFactory;

    protected $connection = 'mysql';
    protected $guarded = [];

    protected $casts = [
        'checklist_definition' => 'array',
        'is_active' => 'boolean',
        'frequency_days' => 'integer',
    ];

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function assets(): HasMany
    {
        return $this->hasMany(Asset::class);
    }
}
