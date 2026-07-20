<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Master resep produksi: definisi ingredient default untuk membuat sebuah
 * produk output. Lihat migration 2026_07_18_000100_create_recipes_table.
 */
class Recipe extends Model
{
    use HasFactory;

    protected $connection = 'mysql';
    protected $guarded = [];

    protected $casts = [
        'output_qty' => 'decimal:3',
        'is_active' => 'boolean',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function outputUnit(): BelongsTo
    {
        return $this->belongsTo(Unit::class, 'output_unit_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }

    public function ingredients(): HasMany
    {
        return $this->hasMany(RecipeIngredient::class);
    }

    public function productions(): HasMany
    {
        return $this->hasMany(Production::class);
    }

    /**
     * Scope: hanya resep aktif (yang dipakai sbg default saat user pilih
     * produk output untuk produksi).
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
