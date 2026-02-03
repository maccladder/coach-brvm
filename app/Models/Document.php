<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\User;
use App\Models\DocumentPurchase;

class Document extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'slug',
        'type',
        'sector_id',
        'country',
        'price',
        'description',
        'file_path',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'price'     => 'integer',
    ];

    /* =====================
     | Relations
     ===================== */
    public function purchases()
    {
        return $this->hasMany(DocumentPurchase::class);
    }

    /* =====================
     | Scopes
     ===================== */
    public function scopeActive($q)
    {
        return $q->where('is_active', true);
    }

    /* =====================
     | Helpers
     ===================== */
    public function isBoughtBy(User $user): bool
    {
        return $this->purchases()
            ->paid()
            ->where('user_id', $user->id)
            ->exists();
    }
}
