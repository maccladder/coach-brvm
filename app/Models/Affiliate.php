<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class Affiliate extends Model
{
    protected $fillable = [
        'user_id',
        'code',
        'custom_code',
        'status',
        'click_count',
        'requested_at',
        'activated_at',
        'activated_by',
        'suspended_at',
        'admin_note',
    ];

    protected $casts = [
        'custom_code'    => 'boolean',
        'click_count'    => 'integer',
        'requested_at'   => 'datetime',
        'activated_at'   => 'datetime',
        'suspended_at'   => 'datetime',
    ];

    // ─── Mutateur : code toujours en majuscules ───────────────────────────────

    public function setCodeAttribute(string $value): void
    {
        $this->attributes['code'] = strtoupper(trim($value));
    }

    // ─── Scopes ───────────────────────────────────────────────────────────────

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', 'pending');
    }

    // ─── Relations ────────────────────────────────────────────────────────────

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function activatedBy()
    {
        return $this->belongsTo(User::class, 'activated_by');
    }

    public function commissions()
    {
        return $this->hasMany(AffiliateCommission::class);
    }

    public function payoutRequests()
    {
        return $this->hasMany(AffiliatePayoutRequest::class);
    }

    public function clicks()
    {
        return $this->hasMany(AffiliateClick::class);
    }

    // ─── Helpers ──────────────────────────────────────────────────────────────

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Active l'apporteur et met à jour le cache sur users en même temps.
     * affiliates.status est la source de vérité.
     */
    public function activate(int $adminId): void
    {
        $this->status       = 'active';
        $this->activated_at = now();
        $this->activated_by = $adminId;
        $this->save();

        $this->user()->update(['affiliate_status' => 'active']);
    }

    /**
     * Suspend l'apporteur et met à jour le cache sur users.
     */
    public function suspend(?string $reason = null): void
    {
        $this->status       = 'suspended';
        $this->suspended_at = now();
        if ($reason) {
            $this->admin_note = $reason;
        }
        $this->save();

        $this->user()->update(['affiliate_status' => 'suspended']);
    }
}
