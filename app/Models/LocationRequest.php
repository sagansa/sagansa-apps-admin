<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Record permintaan lokasi on-demand dari admin (sama tabel dengan services/api).
 */
class LocationRequest extends Model
{
    use HasFactory;

    protected $connection = 'mysql';

    // Tabel ini hanya memakai created_at.
    const UPDATED_AT = null;

    protected $guarded = [];

    protected $casts = [
        'created_at' => 'datetime:Y-m-d H:i:s',
        'fulfilled_at' => 'datetime:Y-m-d H:i:s',
        'timed_out_at' => 'datetime:Y-m-d H:i:s',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function requestedBy()
    {
        return $this->belongsTo(User::class, 'requested_by_id');
    }

    public function location()
    {
        return $this->hasOne(EmployeeLocation::class, 'request_id', 'request_id');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }
}
