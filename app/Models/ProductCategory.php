<?php

namespace App\Models;

use App\Support\PublicStorageUrl;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Str;

class ProductCategory extends Model
{

    protected $connection = 'mysql';
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'description',
        'status'
    ];

    protected $casts = [
        'status' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime'
    ];

    // Relasi ke products
    public function products()
    {
        return $this->hasMany(Product::class, 'category_id');
    }

    // Relasi ke active products
    public function activeProducts()
    {
        return $this->hasMany(Product::class, 'category_id')->where('status', 1);
    }

    // Scope untuk kategori aktif
    public function scopeActive($query)
    {
        return $query->where('status', 1);
    }

    // Scope untuk kategori dengan produk
    public function scopeHasProducts($query)
    {
        return $query->whereHas('products');
    }

    // Scope untuk kategori dengan produk aktif
    public function scopeHasActiveProducts($query)
    {
        return $query->whereHas('products', function ($query) {
            $query->where('status', 1);
        });
    }

    // Method untuk mendapatkan jumlah produk
    public function getProductCountAttribute()
    {
        return $this->products()->count();
    }

    // Method untuk mendapatkan jumlah produk aktif
    public function getActiveProductCountAttribute()
    {
        return $this->products()->where('status', 1)->count();
    }

    // Method untuk mengecek apakah kategori memiliki produk
    public function hasProducts()
    {
        return $this->products()->exists();
    }

    // Method untuk mengecek apakah kategori memiliki produk aktif
    public function hasActiveProducts()
    {
        return $this->products()->where('status', 1)->exists();
    }

    // Method untuk mendapatkan total produk per store
    public function getProductCountByStore($storeId)
    {
        return $this->products()
            ->whereHas('prices', function ($query) use ($storeId) {
                $query->where('store_id', $storeId);
            })
            ->count();
    }

    // Method untuk mendapatkan range harga produk dalam kategori di store tertentu
    public function getPriceRangeByStore($storeId)
    {
        $prices = $this->products()
            ->join('product_prices', 'products.id', '=', 'product_prices.product_id')
            ->where('product_prices.store_id', $storeId)
            ->selectRaw('MIN(product_prices.price) as min_price, MAX(product_prices.price) as max_price')
            ->first();

        return [
            'min' => $prices->min_price,
            'max' => $prices->max_price
        ];
    }

    // Boot method untuk setup model events
    protected static function boot()
    {
        parent::boot();

        // Saat kategori dihapus, update status produk terkait
        static::deleting(function ($category) {
            // Soft delete semua produk dalam kategori
            $category->products()->delete();
        });

        // Saat kategori direstored, restore juga produk terkait
        static::restored(function ($category) {
            $category->products()->onlyTrashed()->restore();
        });
    }

    // Method untuk mendapatkan path gambar kategori (jika ada)
    public function getImagePathAttribute()
    {
        return $this->image ? PublicStorageUrl::from('categories/' . $this->image) : null;
    }

    // Method untuk generate slug (jika diperlukan)
    public function generateSlug()
    {
        return Str::slug($this->name);
    }

    // Method untuk mendapatkan status text
    public function getStatusTextAttribute()
    {
        return $this->status === 1 ? 'Active' : 'Inactive';
    }

    // Method untuk mendapatkan produk terlaris dalam kategori
    public function getTopProducts($limit = 5)
    {
        return $this->products()
            ->where('status', 1)
            ->withCount('orderItems') // Asumsi ada relasi ke order_items
            ->orderByDesc('order_items_count')
            ->limit($limit)
            ->get();
    }

    // Method untuk mendapatkan produk terbaru dalam kategori
    public function getLatestProducts($limit = 5)
    {
        return $this->products()
            ->where('status', 1)
            ->latest()
            ->limit($limit)
            ->get();
    }

    // Method untuk mendapatkan statistik kategori
    public function getStats()
    {
        return [
            'total_products' => $this->product_count,
            'active_products' => $this->active_product_count,
            'has_variants' => $this->products()->where('has_variants', true)->count(),
            'without_variants' => $this->products()->where('has_variants', false)->count(),
            'last_updated' => $this->updated_at->diffForHumans()
        ];
    }
}
