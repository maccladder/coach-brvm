<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MobileMoneyCheckout extends Model
{
    public const WAITING = ['pending', 'send_otp'];
    public const FINAL   = ['success', 'failed', 'replaced'];

    protected $fillable = [
        'user_id', 'type', 'item_key', 'reference', 'amount', 'operator', 'phone',
        'status', 'paystack_status', 'display_text', 'action_url', 'qr_code', 'message',
        'not_debited', 'last_checked_at', 'paid_at', 'meta',
    ];

    protected $casts = [
        'meta'            => 'array',
        'not_debited'     => 'boolean',
        'last_checked_at' => 'datetime',
        'paid_at'         => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function isWaiting(): bool
    {
        return in_array($this->status, self::WAITING, true);
    }

    public function ageInSeconds(): int
    {
        return (int) $this->created_at->diffInSeconds(now(), true);
    }
}
