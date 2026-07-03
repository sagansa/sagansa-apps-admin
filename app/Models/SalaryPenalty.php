<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class SalaryPenalty extends Model
{
    use HasFactory;

    protected $connection = 'mysql';

    protected $guarded = [];

    protected $casts = [
        'date' => 'date',
        'amount' => 'decimal:2',
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
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function monthlySalary()
    {
        return $this->belongsTo(MonthlySalary::class);
    }
}
