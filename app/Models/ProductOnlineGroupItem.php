<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductOnlineGroupItem extends Model
{
    protected $connection = 'mysql';

    protected $guarded = ['id'];

    public function group()
    {
        return $this->belongsTo(ProductOnlineGroup::class, 'product_online_group_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
