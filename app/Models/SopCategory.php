<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Kategori SOP — pengelompokan konten panduan global
 * (mis. Buka Toko, Tutup Toko, Kebersihan, Kasir).
 */
class SopCategory extends Model
{
    use HasFactory;

    protected $connection = 'mysql';
    protected $guarded = [];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function sops(): HasMany
    {
        return $this->hasMany(Sop::class, 'sop_category_id');
    }
}
