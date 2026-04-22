<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

class LettreCIAccess extends Model
{
    protected $table = 'lettre_ci_accesses';

    protected $fillable = [
        'user_id',
        'payment_ref',
        'amount',
        'currency',
        'status',
        'paid_at',
    ];

    protected $casts = [
        'paid_at' => 'datetime',
        'amount'  => 'decimal:2',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }

    public static function hasActiveAccess(User $user): bool
    {
        return static::where('user_id', $user->id)
            ->where('status', 'active')
            ->exists();
    }
}
