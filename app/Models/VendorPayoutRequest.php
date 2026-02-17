<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VendorPayoutRequest extends Model
{
    protected $fillable = [
        'vendor_id',
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
        'meta' => 'array',
        'requested_at' => 'datetime',
        'approved_at' => 'datetime',
        'paid_at' => 'datetime',
    ];

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'vendor_id');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function payer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'paid_by');
    }
}
