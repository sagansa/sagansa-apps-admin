<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductOnlineGroupPriceTier extends Model
{
    protected $connection = 'mysql';

    protected $guarded = ['id'];

    public function group(): BelongsTo
    {
        return $this->belongsTo(ProductOnlineGroup::class, 'product_online_group_id');
    }
}
