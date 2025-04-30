<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Sale extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'shift_id',
        'user_id',
        'total_amount',
        'amount_paid',
        'payment_method',
        'child_id',
        'play_session_id',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'total_amount' => 'decimal:2',
        'amount_paid' => 'decimal:2',
    ];

    /**
     * Attributes that are appended to the serialized model.
     *
     * @var array<int, string>
     */
    protected $appends = [
        'change_given',
    ];

    /**
     * Get the shift that owns the sale.
     */
    public function shift(): BelongsTo
    {
        return $this->belongsTo(Shift::class);
    }

    /**
     * Get the user that created the sale.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
    
    /**
     * Get the items for this sale.
     */
    public function items(): HasMany
    {
        return $this->hasMany(SaleItem::class);
    }
    
    /**
     * Get the child associated with this sale.
     */
    public function child(): BelongsTo
    {
        return $this->belongsTo(Child::class);
    }
    
    /**
     * Get the play session associated with this sale.
     */
    public function play_session(): BelongsTo
    {
        return $this->belongsTo(PlaySession::class);
    }
    
    /**
     * Get the change given back to the customer.
     * 
     * @return float
     */
    public function getChangeGivenAttribute()
    {
        return max(0, $this->amount_paid - $this->total_amount);
    }
} 