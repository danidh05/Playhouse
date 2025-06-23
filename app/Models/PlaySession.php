<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class PlaySession extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'child_id',
        'shift_id',
        'user_id',
        'planned_hours',
        'actual_hours',
        'started_at',
        'ended_at',
        'amount_paid',
        'payment_method',
        'notes',
        'discount_pct',
        'total_cost',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
        'planned_hours' => 'decimal:2',
        'actual_hours' => 'decimal:2',
        'discount_pct' => 'decimal:2',
        'total_cost' => 'decimal:2',
        'amount_paid' => 'decimal:2',
    ];

    /**
     * Get the child that owns the play session.
     */
    public function child(): BelongsTo
    {
        return $this->belongsTo(Child::class);
    }

    /**
     * Get the shift that owns the play session.
     */
    public function shift(): BelongsTo
    {
        return $this->belongsTo(Shift::class);
    }

    /**
     * Get the user that created the play session.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the add-ons for the play session.
     */
    public function addOns(): BelongsToMany
    {
        return $this->belongsToMany(AddOn::class, 'add_on_play_session')
            ->withPivot('qty', 'subtotal')
            ->withTimestamps();
    }

    /**
     * Get the purchases for the play session.
     */
    public function purchases(): HasMany
    {
        return $this->hasMany(Purchase::class);
    }

    /**
     * Get the alert for the play session.
     */
    public function alert(): HasOne
    {
        return $this->hasOne(Alert::class);
    }

    /**
     * Get the sales associated with this play session.
     */
    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class, 'play_session_id');
    }

    /**
     * Get the main sale record for this play session (there should only be one).
     */
    public function sale(): HasOne
    {
        return $this->hasOne(Sale::class, 'play_session_id');
    }

    /**
     * Get start_time attribute (maps to started_at).
     */
    public function getStartTimeAttribute()
    {
        return $this->started_at;
    }

    /**
     * Get end_time attribute (maps to ended_at).
     */
    public function getEndTimeAttribute()
    {
        return $this->ended_at;
    }
} 