<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Keberangkatan extends Model
{
    protected $table = 'keberangkatan';

    protected $fillable = [
        'user_id',
        'maskapai',
        'nomor_penerbangan',
        'tanggal_berangkat',
        'negara_tujuan',
        'status',
    ];

    protected $casts = [
        'tanggal_berangkat' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
