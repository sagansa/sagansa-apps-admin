<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Province extends Model
{

    protected $connection = 'mysql';
    use HasFactory;

    public $timestamps = false;

    protected $guarded = [];

    public function cities()
    {
        return $this->hasMany(City::class);
    }

    public function suppliers()
    {
        return $this->hasMany(Supplier::class);
    }

    public function locations()
    {
        return $this->hasMany(Location::class);
    }

    public function deliveryAddresses()
    {
        return $this->hasMany(DeliveryAddress::class);
    }


    public function postalCodes()
    {
        return $this->hasMany(PostalCode::class);
    }
}
