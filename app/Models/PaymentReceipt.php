<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PaymentReceipt extends Model
{

    protected $connection = 'mysql';
    use HasFactory;

    protected $guarded = [];

    public function fuelServices()
    {
        return $this->belongsToMany(FuelService::class);
    }

    public function dailySalaries()
    {
        return $this->belongsToMany(DailySalary::class);
    }

    public function invoicePurchases()
    {
        return $this->belongsToMany(InvoicePurchase::class)
            ->using(InvoicePurchasePaymentReceipt::class)
            ->distinct();
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relasi helper untuk selector "Created By" pada form fuel/service
     * payment receipt. Tidak terpikir sebagai kolom fisik di tabel — hanya
     * dipakai Filament Select relationship untuk memfilter fuel/service
     * berdasarkan pembuat (created_by) fuel/service.
     */
    public function fuelServiceCreatedBy()
    {
        return $this->belongsTo(User::class, 'fuel_service_created_by');
    }

    public function getPaymentReceiptNameAttribute()
    {
        $paymentReceiptDetails = [
            ($this->supplier->name ?: ''),
            ($this->user->name ?: ''),
        ];

        return $paymentReceiptDetails;
    }
}
