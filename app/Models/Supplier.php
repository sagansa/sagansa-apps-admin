<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Supplier extends Model
{

    protected $connection = 'mysql';
    use HasFactory;

    protected $fillable = [
        'name',
        'no_telp',
        'address',
        'province_id',
        'regency_id',
        'district_id',
        'village_id',
        'bank_id',
        'bank_account_name',
        'bank_account_no',
        'status',
        'image',
        'user_id',
        'postal_code_id',
        'qris'
    ];

    public function province()
    {
        return $this->belongsTo(Province::class);
    }

    public function city()
    {
        return $this->belongsTo(City::class);
    }

    public function district()
    {
        return $this->belongsTo(District::class);
    }

    public function subdistrict()
    {
        return $this->belongsTo(Subdistrict::class);
    }

    public function bank()
    {
        return $this->belongsTo(Bank::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function advancePurchases()
    {
        return $this->hasMany(AdvancePurchase::class);
    }

    public function fuelServices()
    {
        return $this->hasMany(FuelService::class);
    }

    public function invoicePurchases()
    {
        return $this->hasMany(InvoicePurchase::class);
    }

    public function postalCode()
    {
        return $this->belongsTo(PostalCode::class);
    }

    public function getSupplierColumnNameAttribute()
    {
        $supplierName = $this->name ?: '';
        $bankName = $this->bank->name ?: '';
        $accountName = $this->bank_account_name ?: '';
        $accountNo = $this->bank_account_no ?: '';

        return implode(PHP_EOL, [
            $supplierName,
            $bankName,
            $accountName,
            $accountNo,
        ]);
    }

    public function getSupplierNameAttribute()
    {
        $supplierDetails = [
            "Nama: " . ($this->name ?: 'tidak tersedia'),
            "Bank: " . ($this->bank ? $this->bank->name : 'tidak tersedia'),
            "Nama Rek.: " . ($this->bank_account_name ?: 'tidak tersedia'),
            "No. Rek.: " . ($this->bank_account_no ?: 'tidak tersedia'),
        ];

        return implode("\n", $supplierDetails);
    }
}
