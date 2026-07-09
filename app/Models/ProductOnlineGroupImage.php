<?php

namespace App\Models;

use App\Support\PublicStorageUrl;
use Illuminate\Database\Eloquent\Model;

class ProductOnlineGroupImage extends Model
{
    protected $connection = 'mysql';

    protected $table = 'product_online_group_product_image';

    public $timestamps = false;

    protected $fillable = ['product_online_group_id', 'product_image_id', 'order'];

    public function group()
    {
        return $this->belongsTo(ProductOnlineGroup::class, 'product_online_group_id');
    }

    public function image()
    {
        return $this->belongsTo(ProductImage::class, 'product_image_id');
    }

    public function getImageUrlAttribute()
    {
        return $this->image?->getImageUrl() ?? null;
    }
}
