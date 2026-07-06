<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class DetailInvoice extends Model
{

    protected $connection = 'mysql';
    use HasFactory;

    protected $guarded = [];

    protected static function boot(): void
    {
        parent::boot();

        // Auto-update status detail_request ke "done" (2) saat detail invoice dibuat
        static::created(function (self $detailInvoice) {
            if ($detailInvoice->detail_request_id) {
                DetailRequest::where('id', $detailInvoice->detail_request_id)
                    ->where('status', '4') // Hanya yang statusnya approved
                    ->update(['status' => 2]);
            }
        });
    }

    public function invoicePurchase()
    {
        return $this->belongsTo(InvoicePurchase::class);
    }

    public function detailRequest()
    {
        return $this->belongsTo(DetailRequest::class);
    }

    public function unit()
    {
        return $this->belongsTo(Unit::class);
    }

    public function productionMainFroms()
    {
        return $this->hasMany(ProductionMainFrom::class);
    }

    public function getDetailInvoiceNameAttribute()
    {
        $detailInvoices = [
            "Product: " . ($this->detailRequest->product->name ? : ''),
            "Quantity: " . ($this->quantity_product . ' ' . $this->detailRequest->product->unit->unit ? : ''),
            "Date: " . ($this->invoicePurchase->date ? : ''),
        ];

        return implode("\n", $detailInvoices);
    }

    // public function getDetailInvoiceNameAttribute()
    // {
    //     return $this->detailRequest->product->name . ' | ' . $this->quantity_product . ' ' . $this->detailRequest->product->unit->unit . ' | ' . $this->invoicePurchase->date;
    // }


}
