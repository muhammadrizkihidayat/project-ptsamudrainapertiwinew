<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DokumenPelaut extends Model
{
    protected $table = 'dokumen_pelaut';

    protected $fillable = [
        'user_id',
        'jenis_dokumen',
        'nomor_dokumen',
        'tanggal_terbit',
        'tanggal_expired',
        'file_path',
        'status_verifikasi',
    ];

    protected $casts = [
        'tanggal_terbit' => 'date',
        'tanggal_expired' => 'date',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
