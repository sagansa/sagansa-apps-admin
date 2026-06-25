<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Titik lokasi pegawai (sama tabel dengan services/api).
 *
 * Tabel `employee_locations` di DB bisnis (mysql/sagansa). Kolom `created_by_id`
 * merujuk id user di DB auth (mysql_auth), sebagai loose reference cross-DB.
 */
class EmployeeLocation extends Model
{
    use HasFactory;

    protected $connection = 'mysql';

    protected $guarded = [];

    protected $casts = [
        'latitude' => 'float',
        'longitude' => 'float',
        'accuracy' => 'float',
        'captured_at' => 'datetime:Y-m-d H:i:s',
    ];

    /**
     * User pemilik titik lokasi (loose reference, cross-DB).
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }

    public function scopeForUser($query, $userId)
    {
        return $query->where('created_by_id', $userId);
    }

    public function scopeLatestFirst($query)
    {
        return $query->orderBy('captured_at', 'desc');
    }
}
