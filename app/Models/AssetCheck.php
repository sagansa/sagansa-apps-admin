<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AssetCheck extends Model
{
    use HasFactory;

    protected $connection = 'mysql';
    protected $guarded = [];

    protected $casts = [
        'check_date' => 'date',
        'photos' => 'array',
        'latitude' => 'decimal:7',
        'longitude' => 'decimal:7',
        'severity' => 'integer',
        'status' => 'integer',
        'condition_before' => 'integer',
        'condition_after' => 'integer',
    ];

    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class, 'asset_id');
    }

    public function checkedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'checked_by_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(AssetCheckItem::class, 'asset_check_id');
    }

    public function issues(): HasMany
    {
        return $this->hasMany(AssetIssue::class, 'asset_check_id');
    }
}
