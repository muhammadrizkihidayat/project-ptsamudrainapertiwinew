<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActivityLog extends Model
{
    protected $table = 'activity_logs';

    public $timestamps = false; // created_at only, handled by database

    protected $casts = [
        'created_at' => 'datetime',
    ];

    protected $fillable = [
        'user_id',
        'aktivitas',
        'created_at',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
