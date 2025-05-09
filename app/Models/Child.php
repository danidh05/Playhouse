<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Carbon\Carbon;

class Child extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'birth_date',
        'age',
        'guardian_name',
        'guardian_phone',
        'guardian_contact',
        'marketing_sources',
        'marketing_notes',
        'notes',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'birth_date' => 'date',
        'marketing_sources' => 'array',
    ];

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($child) {
            if ($child->birth_date) {
                $child->age = Carbon::parse($child->birth_date)->age;
            }
        });

        static::updating(function ($child) {
            if ($child->isDirty('birth_date') && $child->birth_date) {
                $child->age = Carbon::parse($child->birth_date)->age;
            }
        });
    }

    /**
     * Get the play sessions for the child.
     */
    public function playSessions(): HasMany
    {
        return $this->hasMany(PlaySession::class);
    }

    /**
     * Get the complaints for the child.
     */
    public function complaints(): HasMany
    {
        return $this->hasMany(Complaint::class);
    }
} 