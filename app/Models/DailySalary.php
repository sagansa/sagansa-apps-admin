<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class DailySalary extends Model
{

    protected $connection = 'mysql';
    use HasFactory;

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

    public function getDailySalaryNameAttribute()
    {
        $creatorName = 'Unknown';
        if ($this->relationLoaded('createdBy') && $this->createdBy) {
            $creatorName = $this->createdBy->name;
        } elseif ($this->created_by_id) {
            if (!is_numeric($this->created_by_id)) {
                $user = \App\Models\User::withTrashed()->where('uuid', $this->created_by_id)->first();
                if ($user) $creatorName = $user->name;
            } else {
                $user = \App\Models\User::withTrashed()->find($this->created_by_id);
                if ($user) $creatorName = $user->name;
            }
        }

        return $creatorName .
            ' | ' . $this->date .
            ' | ' . ($this->store?->nickname ?? 'Unknown') .
            ' | Rp ' . number_format($this->amount, 0, ',', '.');
    }

    public function scopeForPaymentType(Builder $query, $paymentTypeId)
    {
        return $query->where('payment_type_id', $paymentTypeId)
            ->whereNull('payment_receipt_id')
            ->orderBy('date', 'asc');
    }
}
