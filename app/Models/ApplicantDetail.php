<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ApplicantDetail extends Model
{
    protected $connection = 'mysql_recruitment';

    protected $guarded = ['id'];

    protected $casts = [
        'join_date' => 'date'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
