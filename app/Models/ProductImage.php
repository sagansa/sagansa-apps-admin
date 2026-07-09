<?php

namespace App\Models;

use App\Support\PublicStorageUrl;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ProductImage extends Model
{

    protected $connection = 'mysql';
    use HasFactory;

    public $timestamps = false;

    protected $fillable = ['product_id', 'image_url', 'order'];

    public static function boot()
    {
        parent::boot();

        static::saved(function (ProductImage $image) {
            if ($image->product) {
                Product::syncImageFromRelation($image->product);
            }
        });

        static::deleted(function (ProductImage $image) {
            if ($image->product) {
                Product::syncImageFromRelation($image->product);
            }
        });
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function getImageUrl(): string
    {
        $path = $this->getRawOriginal('image_url');

        if (!$path) {
            return 'https://placehold.co/600x400?text=No+Image';
        }

        return PublicStorageUrl::from($path);
    }
}
