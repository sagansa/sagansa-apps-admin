<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Bank extends Model
{

    protected $connection = 'mysql';
    use HasFactory;

    protected $guarded = [];


    public function closingCouriers()
    {
        return $this->hasMany(ClosingCourier::class);
    }

    public function suppliers()
    {
        return $this->hasMany(Supplier::class);
    }

    public function transferToAccounts()
    {
        return $this->hasMany(TransferToAccount::class);
    }
}
