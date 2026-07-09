<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class DetailSalesOrder extends Model
{

    protected $connection = 'mysql';
    use HasFactory;

    protected $guarded = [];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function productOnlineGroup()
    {
        return $this->belongsTo(ProductOnlineGroup::class);
    }

    public function salesOrder()
    {
        return $this->belongsTo(SalesOrder::class);
    }

    public function salesOrderOnline()
    {
        return $this->belongsTo(SalesOrderOnline::class, 'sales_order_id', 'id');
    }

    public function salesOrderDirect()
    {
        return $this->belongsTo(SalesOrderDirect::class, 'sales_order_id', 'id');
    }

    public function salesOrderEmployee()
    {
        return $this->belongsTo(SalesOrderEmployee::class, 'sales_order_id', 'id');
    }
}
