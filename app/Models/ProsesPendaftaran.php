<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProsesPendaftaran extends Model
{
    protected $table = 'proses_pendaftaran';

    protected $fillable = [
        'user_id',
        'tahap',
        'status',
        'catatan',
        'tanggal_update',
    ];

    protected $casts = [
        'tanggal_update' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
