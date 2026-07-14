<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JobOrder extends Model
{
    protected $table = 'job_orders';

    protected $fillable = [
        'user_id',
        'nama_kapal',
        'negara_tujuan',
        'posisi',
        'status_job',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
