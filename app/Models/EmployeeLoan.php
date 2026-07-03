<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class EmployeeLoan extends Model
{
    use HasFactory;

    protected $connection = 'mysql';

    protected $guarded = [];

    protected $casts = [
        'loan_date' => 'date',
        'amount' => 'decimal:2',
        'installment_amount' => 'decimal:2',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->tenant_id)) {
                $user = \App\Models\User::find($model->user_id);
                $model->tenant_id = $user?->tenant_id 
                    ?? \App\Models\Store::first()?->tenant_id 
                    ?? '00000000-0000-0000-0000-000000000000';
            }
        });

        static::created(function ($model) {
            // Generate installments
            $due = \Carbon\Carbon::parse($model->loan_date)->startOfMonth();
            for ($i = 0; $i < $model->installment_count; $i++) {
                $model->installments()->create([
                    'amount' => $model->installment_amount,
                    'due_date' => $due->copy()->addMonths($i)->toDateString(),
                    'status' => 1 // 1 = pending
                ]);
            }
        });
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function installments()
    {
        return $this->hasMany(LoanInstallment::class);
    }
}
