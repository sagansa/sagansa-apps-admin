<?php

namespace App\Models;

use App\Support\PublicStorageUrl;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class SalesOrderOnline extends Model
{

    protected $connection = 'mysql';
    use HasFactory, SoftDeletes;

    protected $guarded = [];

    protected $table = 'sales_orders';

    public function deliveryService()
    {
        return $this->belongsTo(DeliveryService::class);
    }

    public function transferToAccount()
    {
        return $this->belongsTo(TransferToAccount::class);
    }

    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    public function orderedBy()
    {
        return $this->belongsTo(User::class, 'ordered_by_id');
    }

    public function assignedBy()
    {
        return $this->belongsTo(User::class, 'assigned_by_id');
    }

    public function onlineShopProvider()
    {
        return $this->belongsTo(OnlineShopProvider::class);
    }

    public function deliveryAddress()
    {
        return $this->belongsTo(DeliveryAddress::class);
    }

    public function detailSalesOrders()
    {
        return $this->hasMany(DetailSalesOrder::class, 'sales_order_id');
    }

    // Atribut atau accessor untuk URL gambar
    public function getImagePaymentUrlAttribute()
    {
        return PublicStorageUrl::from($this->image_payment);
    }
}
