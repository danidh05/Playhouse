<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Shift extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'cashier_id',
        'user_id',
        'date',
        'type',
        'opening_amount',
        'closing_amount',
        'notes',
        'opened_at',
        'closed_at',
        'starting_time',
        'ending_time',
        'expected_ending_time',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'date' => 'date',
        'opening_amount' => 'decimal:2',
        'closing_amount' => 'decimal:2',
        'opened_at' => 'datetime',
        'closed_at' => 'datetime',
        'starting_time' => 'datetime',
        'ending_time' => 'datetime',
        'expected_ending_time' => 'datetime',
    ];

    /**
     * Sync the user_id with cashier_id
     */
    protected static function booted()
    {
        static::creating(function ($shift) {
            if (!empty($shift->user_id) && empty($shift->cashier_id)) {
                $shift->cashier_id = $shift->user_id;
            }
            
            if (!empty($shift->cashier_id) && empty($shift->user_id)) {
                $shift->user_id = $shift->cashier_id;
            }
            
            if (!empty($shift->starting_time) && empty($shift->opened_at)) {
                $shift->opened_at = $shift->starting_time;
            }
            
            if (!empty($shift->opened_at) && empty($shift->starting_time)) {
                $shift->starting_time = $shift->opened_at;
            }
            
            if (!empty($shift->ending_time) && empty($shift->closed_at)) {
                $shift->closed_at = $shift->ending_time;
            }
            
            if (!empty($shift->closed_at) && empty($shift->ending_time)) {
                $shift->ending_time = $shift->closed_at;
            }
        });
        
        static::updating(function ($shift) {
            if ($shift->isDirty('user_id') && !$shift->isDirty('cashier_id')) {
                $shift->cashier_id = $shift->user_id;
            }
            
            if ($shift->isDirty('cashier_id') && !$shift->isDirty('user_id')) {
                $shift->user_id = $shift->cashier_id;
            }
            
            if ($shift->isDirty('starting_time') && !$shift->isDirty('opened_at')) {
                $shift->opened_at = $shift->starting_time;
            }
            
            if ($shift->isDirty('opened_at') && !$shift->isDirty('starting_time')) {
                $shift->starting_time = $shift->opened_at;
            }
            
            if ($shift->isDirty('ending_time') && !$shift->isDirty('closed_at')) {
                $shift->closed_at = $shift->ending_time;
            }
            
            if ($shift->isDirty('closed_at') && !$shift->isDirty('ending_time')) {
                $shift->ending_time = $shift->closed_at;
            }
        });
    }

    /**
     * Get the cashier that owns the shift.
     */
    public function cashier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cashier_id');
    }

    /**
     * Get the sales for the shift.
     */
    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class);
    }

    /**
     * Get the play sessions for the shift.
     */
    public function playSessions(): HasMany
    {
        return $this->hasMany(PlaySession::class);
    }

    /**
     * Get the complaints for the shift.
     */
    public function complaints(): HasMany
    {
        return $this->hasMany(Complaint::class);
    }

    /**
     * Get the expenses for the shift.
     */
    public function expenses(): HasMany
    {
        return $this->hasMany(Expense::class);
    }

    /**
     * Check if the shift is currently open.
     */
    public function isOpen(): bool
    {
        return $this->opened_at !== null && $this->closed_at === null;
    }

    /**
     * Calculate the expected cash amount at the end of the shift.
     */
    public function expectedCash(): float
    {
        // Start with opening amount
        $expected = $this->opening_amount;
        
        // Add cash sales (using correct column name)
        $expected += $this->sales()
            ->where('payment_method', 'cash')
            ->sum('total_amount');
            
        // Add cash play sessions (using total_cost for accurate revenue)
        $expected += $this->playSessions()
            ->where('payment_method', 'cash')
            ->whereNotNull('total_cost')
            ->sum('total_cost');
            
        return (float) $expected;
    }

    /**
     * Calculate the difference between expected and actual cash.
     */
    public function cashDifference(): ?float
    {
        if ($this->closed_at === null) {
            return null;
        }
        
        return (float) ($this->closing_amount - $this->expectedCash());
    }

    /**
     * Get total card sales and payments.
     */
    public function cardTotal(): float
    {
        $cardSales = $this->sales()
            ->whereIn('payment_method', ['card', 'credit card', 'debit card'])
            ->sum('total_amount');
            
        $cardPlaySessions = $this->playSessions()
            ->whereIn('payment_method', ['card', 'credit card', 'debit card'])
            ->whereNotNull('total_cost')
            ->sum('total_cost');
            
        return (float) ($cardSales + $cardPlaySessions);
    }

    /**
     * Calculate the total sales amount for this shift.
     */
    public function totalSales(): float
    {
        return $this->sales->sum('total_amount');
    }

    /**
     * Calculate the total play sessions amount for this shift.
     */
    public function totalPlaySessions(): float
    {
        return $this->playSessions->whereNotNull('total_cost')->sum('total_cost');
    }

    /**
     * Calculate the cash variance (difference between expected and actual).
     */
    public function cashVariance(): float
    {
        if ($this->closing_amount === null) {
            return 0;
        }
        
        return $this->closing_amount - $this->expectedCash();
    }
} 