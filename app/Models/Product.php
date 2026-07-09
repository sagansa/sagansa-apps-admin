<?php

namespace App\Models;

use Illuminate\Support\Str;
use App\Support\PublicStorageUrl;
use App\Models\DetailInvoice;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Product extends Model
{

    protected $connection = 'mysql';
    use HasFactory;
    use SoftDeletes;

    protected $guarded = [];
    protected $appends = ['image_url'];

    public function getImageUrlAttribute()
    {
        $path = $this->attributes['image'] ?? null;

        if (!$path) {
            return 'https://placehold.co/600x400?text=No+Image';
        }

        return PublicStorageUrl::from($path);
    }

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'deleted_at' => 'datetime',
            'is_asset' => 'boolean',
        ];
    }

    /**
     * Kategori aset (jika produk ditandai sebagai aset).
     */
    public function assetCategory()
    {
        return $this->belongsTo(AssetCategory::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function detailAdvancePurchases()
    {
        return $this->hasMany(DetailAdvancePurchase::class);
    }

    public function detailSalesOrders()
    {
        return $this->hasMany(DetailSalesOrder::class);
    }

    public function unit()
    {
        return $this->belongsTo(Unit::class);
    }

    public function detailRequests()
    {
        return $this->hasMany(DetailRequest::class);
    }

    public function productionSupportFroms()
    {
        return $this->hasMany(ProductionSupportFrom::class);
    }

    public function productionTos()
    {
        return $this->hasMany(ProductionTo::class);
    }

    public function remainingStocks()
    {
        return $this->belongsToMany(RemainingStock::class);
    }

    public function selfConsumptions()
    {
        return $this->belongsToMany(SelfConsumption::class);
    }

    public function transferStocks()
    {
        return $this->belongsToMany(TransferStock::class);
    }

    public function movementAssets()
    {
        return $this->hasMany(MovementAsset::class);
    }

    public function paymentType()
    {
        return $this->belongsTo(PaymentType::class);
    }

    public function materialGroup()
    {
        return $this->belongsTo(MaterialGroup::class);
    }

    public function onlineCategory()
    {
        return $this->belongsTo(OnlineCategory::class);
    }

    public function storageStocks()
    {
        return $this->belongsToMany(StorageStock::class, 'product_storage_stock')
            ->withPivot('quantity');
    }

    public function detailStockCards()
    {
        return $this->hasMany(DetailStockCard::class);
    }

    public function detailTransferCards()
    {
        return $this->hasMany(DetailTransferCard::class);
    }

    public function stockMonitoringDetails()
    {
        return $this->hasMany(StockMonitoringDetail::class);
    }

    public function latestStockCard()
    {
        return $this->hasOne(DetailStockCard::class)
            ->select('detail_stock_cards.*', 'stock_cards.date')
            ->join('stock_cards', 'detail_stock_cards.stock_card_id', '=', 'stock_cards.id')
            ->orderByDesc('stock_cards.date')
            ->orderByDesc('detail_stock_cards.created_at')
            ->withoutGlobalScopes();
    }

    public function images()
    {
        return $this->hasMany(ProductImage::class)->orderBy('order');
    }

    public function priceTiers()
    {
        return $this->hasMany(PriceTier::class)->orderBy('min_quantity');
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

    public static function boot()
    {
        parent::boot();

        static::saving(function ($model) {
            if (empty($model->slug)) {
                $model->slug = Str::slug($model->name);
            }
        });

        static::saved(function ($product) {
            $firstImage = $product->images()->orderBy('order')->value('image_url');

            if ($product->getOriginal('image') !== $firstImage) {
                Product::withoutTimestamps(fn () =>
                    Product::where('id', $product->id)->update(['image' => $firstImage])
                );
            }
        });
    }

    public function getProductNameAttribute()
    {
        return $this->name . ' - ' . $this->unit->unit;
    }

    public function getLatestPrices($limit = 5)
    {
        return DetailInvoice::query()
            ->join('detail_requests', 'detail_invoices.detail_request_id', '=', 'detail_requests.id')
            ->join('invoice_purchases', 'detail_invoices.invoice_purchase_id', '=', 'invoice_purchases.id')
            ->where('detail_requests.product_id', $this->id)
            ->where('detail_invoices.quantity_product', '>', 0)
            ->where('detail_invoices.subtotal_invoice', '>', 0)
            ->orderByDesc('invoice_purchases.date')
            ->orderByDesc('detail_invoices.created_at')
            ->limit($limit)
            ->get([
                'detail_invoices.subtotal_invoice',
                'detail_invoices.quantity_product',
                'invoice_purchases.date'
            ])
            ->map(function ($detail) {
                return [
                    'price' => (float)$detail->subtotal_invoice / (float)$detail->quantity_product,
                    'date' => $detail->date,
                ];
            });
    }

    /**
     * Versi batch dari getLatestPrices: ambil history harga N transaksi terakhir
     * untuk banyak produk sekaligus dalam 1 query (menghindari N+1 saat dipanggil
     * dalam loop per-detail-invoice).
     *
     * @param  list<int|string>  $productIds
     * @return \Illuminate\Support\Collection<int, \Illuminate\Support\Collection>  keyed by product_id
     */
    public static function latestPricesForProducts(array $productIds, int $limit = 5): \Illuminate\Support\Collection
    {
        $productIds = array_values(array_filter($productIds));
        if (empty($productIds)) {
            return collect();
        }

        // Ambil semua baris detail untuk produk-produk tsb, urutkan per produk,
        // lalu kelompokkan & ambil $limit teratas per produk di sisi PHP.
        $rows = DetailInvoice::query()
            ->join('detail_requests', 'detail_invoices.detail_request_id', '=', 'detail_requests.id')
            ->join('invoice_purchases', 'detail_invoices.invoice_purchase_id', '=', 'invoice_purchases.id')
            ->whereIn('detail_requests.product_id', $productIds)
            ->where('detail_invoices.quantity_product', '>', 0)
            ->where('detail_invoices.subtotal_invoice', '>', 0)
            ->orderByRaw('detail_requests.product_id, invoice_purchases.date DESC, detail_invoices.created_at DESC')
            ->get([
                'detail_requests.product_id',
                'detail_invoices.subtotal_invoice',
                'detail_invoices.quantity_product',
                'invoice_purchases.date',
            ]);

        return $rows->groupBy('product_id')->map(function ($group) use ($limit) {
            return $group->take($limit)->map(fn ($detail) => [
                'price' => (float) $detail->subtotal_invoice / (float) $detail->quantity_product,
                'date'  => $detail->date,
            ])->values();
        });
    }
}
