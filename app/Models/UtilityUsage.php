<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class UtilityUsage extends Model
{
    const STATUS_BELUM_DIPERIKSA = 1;
    const STATUS_VALID = 2;
    const STATUS_DIPERBAIKI = 3;
    const STATUS_PERIKSA_ULANG = 4;

    protected $connection = 'mysql';
    use HasFactory;

    protected $guarded = [];

    public function utility()
    {
        return $this->belongsTo(Utility::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by_id');
    }
}
