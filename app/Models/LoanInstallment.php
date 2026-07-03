<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class LoanInstallment extends Model
{
    use HasFactory;

    protected $connection = 'mysql';

    protected $guarded = [];

    protected $casts = [
        'due_date' => 'date',
        'amount' => 'decimal:2',
    ];

    public function employeeLoan()
    {
        return $this->belongsTo(EmployeeLoan::class);
    }

    public function monthlySalary()
    {
        return $this->belongsTo(MonthlySalary::class);
    }
}
