<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

use Spatie\Permission\Traits\HasRoles;
use App\Models\ProfilAbk;
use App\Models\Dokumen;
use App\Models\MedicalCheckup;
use App\Models\Diklat;
use App\Models\DokumenPelaut;
use App\Models\JobOrder;
use App\Models\Keberangkatan;
use App\Models\ProsesPendaftaran;
use App\Models\Notification;
use App\Models\ActivityLog;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'status_akun',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function profilAbk()
    {
        return $this->hasOne(ProfilAbk::class);
    }

    public function dokumen()
    {
        return $this->hasMany(Dokumen::class);
    }

    public function medicalCheckups()
    {
        return $this->hasMany(MedicalCheckup::class);
    }

    public function diklat()
    {
        return $this->hasMany(Diklat::class);
    }

    public function dokumenPelaut()
    {
        return $this->hasMany(DokumenPelaut::class);
    }

    public function jobOrders()
    {
        return $this->hasMany(JobOrder::class);
    }

    public function keberangkatan()
    {
        return $this->hasMany(Keberangkatan::class);
    }

    public function prosesPendaftaran()
    {
        return $this->hasMany(ProsesPendaftaran::class);
    }

    public function notifications()
    {
        return $this->hasMany(Notification::class);
    }

    public function activityLogs()
    {
        return $this->hasMany(ActivityLog::class);
    }
}
