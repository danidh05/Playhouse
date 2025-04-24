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
    ];

    /**
     * Attributes that are appended to the serialized model.
     *
     * @var array<int, string>
     */
    protected $appends = [
        'amount_paid',
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
     * Get the amount paid value.
     * This is an approximation based on typical payment handling.
     * 
     * @return float
     */
    public function getAmountPaidAttribute()
    {
        // For USD, we'd typically round up to nearest dollar for cash payments
        // For LBP, we'd typically use the exact amount
        if ($this->payment_method === 'USD') {
            // Round up to the nearest dollar
            return ceil($this->total_amount);
        }
        
        // For LBP, return the exact amount
        return $this->total_amount;
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