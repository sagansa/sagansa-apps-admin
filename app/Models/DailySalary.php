<?php

namespace App\Models;

use App\Support\ResolvesCreatedBy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class DailySalary extends Model
{

    protected $connection = 'mysql';
    use HasFactory;
    use ResolvesCreatedBy;

    protected $guarded = [];

    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    public function shiftStore()
    {
        return $this->belongsTo(ShiftStore::class);
    }

    public function presence()
    {
        return $this->belongsTo(Presence::class);
    }

    public function paymentType()
    {
        return $this->belongsTo(PaymentType::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by_id');
    }

    public function closingStores()
    {
        return $this->belongsToMany(ClosingStore::class);
    }

    public function paymentReceipts()
    {
        return $this->belongsToMany(PaymentReceipt::class);
    }

    public function getDailySalaryNameAttribute(): string
    {
        $creatorName = $this->relationLoaded('createdBy') && $this->createdBy
            ? $this->createdBy->name
            : self::findCreatorName($this->created_by_id);

        $creatorName = $creatorName !== '' ? $creatorName : 'Unknown';

        return $creatorName .
            ' | ' . $this->date .
            ' | ' . ($this->store?->nickname ?? 'Unknown') .
            ' | Rp ' . number_format($this->amount, 0, ',', '.');
    }

    /**
     * Scope record daily salary berdasarkan payment_type_id DAN belum terikat
     * ke payment receipt manapun.
     *
     * Versi sebelumnya salah merujuk kolom `payment_receipt_id` yang tidak ada
     * di tabel daily_salaries (relasi via pivot) — akan throw SQL error.
     * Sekarang memakai whereDoesntHave pivot. Tidak menambah filter status
     * agar tidak mengubah behavior caller yang sudah ada
     * (DailySalariesRelationManager memanggil scope ini).
     */
    public function scopeForPaymentType(Builder $query, $paymentTypeId = '1'): Builder
    {
        return $query
            ->where('payment_type_id', $paymentTypeId)
            ->whereDoesntHave('paymentReceipts')
            ->orderBy('date', 'asc');
    }
}
