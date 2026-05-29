<?php

namespace App\Models;

use App\Support\PublicStorageUrl;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ProductImage extends Model
{

    protected $connection = 'mysql';
    use HasFactory;

    protected $fillable = ['product_id', 'image_url', 'order'];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function getImageUrlAttribute($value)
    {
        if (!$value) {
            return 'https://placehold.co/600x400?text=No+Image';
        }

        return PublicStorageUrl::from($value);
    }
}
