<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AffiliatePayoutRequest extends Model
{
    protected $fillable = [
        'affiliate_id',
        'amount',
        'status',
        'payout_method',
        'payout_account',
        'reference',
        'admin_note',
        'requested_at',
        'approved_at',
        'paid_at',
        'approved_by',
        'paid_by',
        'meta',
    ];

    protected $casts = [
        'amount'       => 'integer',
        'requested_at' => 'datetime',
        'approved_at'  => 'datetime',
        'paid_at'      => 'datetime',
        'meta'         => 'array',
    ];

    // ─── Relations ────────────────────────────────────────────────────────────

    public function affiliate()
    {
        return $this->belongsTo(Affiliate::class);
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function payer()
    {
        return $this->belongsTo(User::class, 'paid_by');
    }
}
