<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProfilAbk extends Model
{
    protected $table = 'profil_abk';

    protected $fillable = [
        'user_id',
        'nama_lengkap',
        'tempat_lahir',
        'tanggal_lahir',
        'alamat',
        'nomor_hp',
        'pengalaman_kerja',
        'posisi_dilamar',
        'foto_profil',
    ];

    protected $casts = [
        'tanggal_lahir' => 'date',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
