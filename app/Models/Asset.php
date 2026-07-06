<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Instance aset (modul baru, berbasis produk). Dipakai oleh Filament admin
 * untuk monitoring. Create/update utama dilakukan via app mobile atau
 * auto-link dari procurement invoice.
 */
class Asset extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $connection = 'mysql';
    protected $guarded = [];

    protected $casts = [
        'next_check_at' => 'datetime',
        'last_check_at' => 'datetime',
        'purchase_date' => 'date',
        'condition' => 'integer',
        'status' => 'integer',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(AssetCategory::class, 'asset_category_id');
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }

    public function checks(): HasMany
    {
        return $this->hasMany(AssetCheck::class, 'asset_id');
    }

    public function issues(): HasMany
    {
        return $this->hasMany(AssetIssue::class, 'asset_id');
    }
}
