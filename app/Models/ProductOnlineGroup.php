<?php

namespace App\Models;

use App\Support\PublicStorageUrl;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ProductOnlineGroup extends Model
{
    protected $connection = 'mysql';

    use HasFactory;
    use SoftDeletes;

    protected $guarded = ['id'];
    protected $appends = ['image_url', 'combined_stock'];

    public static function boot()
    {
        parent::boot();

        static::saved(function ($group) {
            $first = $group->images()->with('image')->orderBy('order')->first();
            $url = $first?->image?->getImageUrl();

            if ($group->getOriginal('image') !== $url) {
                ProductOnlineGroup::withoutTimestamps(fn () =>
                    ProductOnlineGroup::where('id', $group->id)->update(['image' => $url])
                );
            }
        });
    }

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'online_price' => 'decimal:2',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];
    }

    public function items()
    {
        return $this->hasMany(ProductOnlineGroupItem::class);
    }

    public function images()
    {
        return $this->hasMany(ProductOnlineGroupImage::class, 'product_online_group_id')->orderBy('order');
    }

    public function products()
    {
        return $this->belongsToMany(Product::class, 'product_online_group_items');
    }

    public function onlineCategory()
    {
        return $this->belongsTo(OnlineCategory::class);
    }

    public function unit()
    {
        return $this->belongsTo(Unit::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function priceTiers()
    {
        return $this->hasMany(ProductOnlineGroupPriceTier::class, 'product_online_group_id')->orderBy('min_quantity');
    }

    public function getImageUrlAttribute()
    {
        $pivotImage = $this->images()->with('image')->first()?->image?->getImageUrl();
        if ($pivotImage) {
            return $pivotImage;
        }

        return PublicStorageUrl::from(
            $this->attributes['image'] ?? null,
            'https://placehold.co/600x400?text=No+Image'
        );
    }

    public function getCombinedStockAttribute(): ?int
    {
        $memberProductIds = $this->items()->pluck('product_id');

        if ($memberProductIds->isEmpty()) {
            return 0;
        }

        if (!Schema::hasTable('stock_cards') || !Schema::hasTable('detail_stock_cards')) {
            return null;
        }

        $latestDate = DB::table('stock_cards')->max('date');
        if (!$latestDate) return null;

        $latestCardIds = DB::table('stock_cards')
            ->where('date', $latestDate)
            ->pluck('id');

        if ($latestCardIds->isEmpty()) {
            return null;
        }

        $total = DB::table('detail_stock_cards')
            ->whereIn('stock_card_id', $latestCardIds)
            ->whereIn('product_id', $memberProductIds)
            ->sum('quantity');

        return (int) $total;
    }

    public function getPriceByQuantity($quantity)
    {
        $tier = $this->priceTiers()
            ->where('min_quantity', '<=', $quantity)
            ->where(function ($query) use ($quantity) {
                $query->whereNull('max_quantity')
                    ->orWhere('max_quantity', '>=', $quantity);
            })
            ->orderByDesc('min_quantity')
            ->first();

        return $tier ? $tier->price : ($this->online_price ?? 0);
    }
}
