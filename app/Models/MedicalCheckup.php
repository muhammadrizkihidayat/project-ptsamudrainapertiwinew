<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MedicalCheckup extends Model
{
    protected $table = 'medical_checkups';

    protected $fillable = [
        'user_id',
        'file_hasil_mcu',
        'status_mcu',
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
