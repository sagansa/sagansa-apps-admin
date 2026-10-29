<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * SOP terstruktur: judul, deskripsi, cover, dan langkah-langkah bergambar.
 * `revision` naik otomatis saat admin mengubah isi (mutate di SopResource
 * Pages); hanya `is_published` yang disajikan ke mobile.
 */
class Sop extends Model
{
    use HasFactory;

    protected $connection = 'mysql';
    protected $table = 'sops';
    protected $guarded = [];

    protected $casts = [
        'is_published' => 'boolean',
        'sort_order' => 'integer',
        'revision' => 'integer',
        'published_at' => 'datetime',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(SopCategory::class, 'sop_category_id');
    }

    public function steps(): HasMany
    {
        return $this->hasMany(SopStep::class, 'sop_id')->orderBy('sort_order');
    }
}
