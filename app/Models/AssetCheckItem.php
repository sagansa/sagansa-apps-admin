<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssetCheckItem extends Model
{
    use HasFactory;

    protected $connection = 'mysql';
    protected $guarded = [];

    protected $casts = [
        'value' => 'integer',
    ];

    public function assetCheck(): BelongsTo
    {
        return $this->belongsTo(AssetCheck::class, 'asset_check_id');
    }
}
