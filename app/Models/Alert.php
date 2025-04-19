<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Alert extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'play_session_id',
        'fired_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'fired_at' => 'datetime',
    ];

    /**
     * Get the play session that owns the alert.
     */
    public function playSession(): BelongsTo
    {
        return $this->belongsTo(PlaySession::class);
    }
} 