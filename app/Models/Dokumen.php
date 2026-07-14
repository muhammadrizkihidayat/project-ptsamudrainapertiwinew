<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Dokumen extends Model
{
    protected $table = 'dokumen';

    protected $fillable = [
        'user_id',
        'jenis_dokumen',
        'file_path',
        'status',
        'catatan_operator',
        'tanggal_upload',
    ];

    protected $casts = [
        'tanggal_upload' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
