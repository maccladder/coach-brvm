<?php

namespace App\Models;

use App\Traits\HasAdminGrants;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Document extends Model
{
    use HasFactory, HasAdminGrants;

    protected $fillable = [
        'vendor_id',

        'title',
        'slug',
        'type',
        'sector_id',
        'country',
        'price',
        'description',
        'file_path',

        'is_active',
        'status',
        'approved_at',
        'approved_by',
        'rejected_note',
    ];

    protected $casts = [
        'is_active'    => 'boolean',
        'price'        => 'integer',
        'approved_at'  => 'datetime',
    ];

    /* =====================
     | Relations
     ===================== */
    public function purchases()
    {
        return $this->hasMany(DocumentPurchase::class);
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'vendor_id');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /* =====================
     | Scopes
     ===================== */
    public function scopeActive($q)
    {
        return $q->where('is_active', true)->where('status', 'published');
    }

    public function scopePublished($q)
    {
        return $q->where('status', 'published');
    }

    /* =====================
     | Helpers
     ===================== */
    public function isBoughtBy(User $user): bool
    {
        return $this->purchases()
                ->paid()
                ->where('user_id', $user->id)
                ->exists()
            || $this->isGrantedTo($user);
    }

    public function isVendorUploaded(): bool
    {
        return !is_null($this->vendor_id);
    }
}
