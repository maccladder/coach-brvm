<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class AffiliateCommission extends Model
{
    protected $fillable = [
        'affiliate_id',
        'payment_id',
        'buyer_user_id',
        'product_type',
        'product_id',
        'base_amount',
        'commission_rate',
        'commission_amount',
        'discount_amount',
        'status',
        'validated_at',
        'paid_at',
        'cancelled_at',
        'cancel_reason',
    ];

    protected $casts = [
        'commission_rate'   => 'float',
        'base_amount'       => 'integer',
        'commission_amount' => 'integer',
        'discount_amount'   => 'integer',
        'validated_at'      => 'datetime',
        'paid_at'           => 'datetime',
        'cancelled_at'      => 'datetime',
    ];

    // ─── Scopes ───────────────────────────────────────────────────────────────

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', 'pending');
    }

    public function scopeValidated(Builder $query): Builder
    {
        return $query->where('status', 'validated');
    }

    // Commissions qui contribuent au solde disponible (retirable)
    public function scopeRetirable(Builder $query): Builder
    {
        return $query->whereIn('status', ['validated']);
    }

    // ─── Relations ────────────────────────────────────────────────────────────

    public function affiliate()
    {
        return $this->belongsTo(Affiliate::class);
    }

    public function payment()
    {
        return $this->belongsTo(Payment::class);
    }

    public function buyer()
    {
        return $this->belongsTo(User::class, 'buyer_user_id');
    }
}
