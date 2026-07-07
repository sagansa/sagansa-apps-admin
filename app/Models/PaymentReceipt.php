<?php

namespace App\Models;

use App\Enum\PaymentFor;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PaymentReceipt extends Model
{

    protected $connection = 'mysql';
    use HasFactory;

    /**
     * Field yang boleh di-mass-assign. Sebelumnya pakai $guarded = [] yang
     * berisiko (semua kolom terbuka, termasuk id/timestamps jika manipulated).
     */
    protected $fillable = [
        'image',
        'amount',
        'payment_for',
        'image_adjust',
        'notes',
        'total_amount',
        'transfer_amount',
        'supplier_id',
        'user_id',
    ];

    protected $casts = [
        'payment_for' => PaymentFor::class,
        'amount' => 'integer',
        'total_amount' => 'integer',
        'transfer_amount' => 'integer',
        'supplier_id' => 'integer',
        'user_id' => 'integer',
    ];

    public function fuelServices(): BelongsToMany
    {
        return $this->belongsToMany(FuelService::class);
    }

    public function dailySalaries(): BelongsToMany
    {
        return $this->belongsToMany(DailySalary::class);
    }

    public function invoicePurchases(): BelongsToMany
    {
        return $this->belongsToMany(InvoicePurchase::class)
            ->using(InvoicePurchasePaymentReceipt::class)
            ->distinct();
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Hasil nama payment receipt untuk display. Sebelumnya mengakses
     * $this->supplier->name tanpa null-safe (bisa error) dan mengembalikan
     * array (tipe tidak lazim untuk accessor "Name").
     */
    public function getPaymentReceiptNameAttribute(): string
    {
        return collect([
            $this->supplier?->name,
            $this->user?->name,
        ])->filter()->implode(' - ');
    }
}
