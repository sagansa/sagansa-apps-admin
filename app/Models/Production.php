<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Production extends Model
{

    protected $connection = 'mysql';
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'applied_at' => 'datetime',
    ];

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by_id');
    }

    /**
     * Resep master yang dipakai sebagai starting point produksi ini.
     * Nullable: produksi boleh tanpa resep (fully manual).
     */
    public function recipe(): BelongsTo
    {
        return $this->belongsTo(Recipe::class);
    }

    /**
     * Item produksi terpadu (bahan baku + output) — model utama yang dipakai
     * mulai fase recipe. Lihat ProductionItem.
     */
    public function items(): HasMany
    {
        return $this->hasMany(ProductionItem::class);
    }

    /** Shortcut: hanya item bahan baku (direction=in). */
    public function inputItems(): HasMany
    {
        return $this->hasMany(ProductionItem::class)->where('direction', 'in');
    }

    /** Shortcut: hanya item hasil (direction=out). */
    public function outputItems(): HasMany
    {
        return $this->hasMany(ProductionItem::class)->where('direction', 'out');
    }

    /**
     * Apakah mutasi stok sudah pernah di-apply untuk produksi ini?
     * Idempotensi ledger: cek kolom ini sebelum apply agar tidak dobel.
     */
    public function isApplied(): bool
    {
        return $this->applied_at !== null;
    }

    // === Relasi LEGACY (kompatibilitas mundur dgn Filament lama) ===
    // Tetap dipertahankan sementara agar RelationManager lama tidak error
    // sebelum di-refactor di fase admin UI.
    public function productionSupportFroms(): HasMany
    {
        return $this->hasMany(ProductionSupportFrom::class);
    }

    public function productionTos(): HasMany
    {
        return $this->hasMany(ProductionTo::class);
    }

    public function productionMainFroms(): HasMany
    {
        return $this->hasMany(ProductionMainFrom::class);
    }
}
