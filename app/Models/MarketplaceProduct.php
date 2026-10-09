<?php

namespace App\Models;

use App\Services\Marketplace\VignetteService;
use App\Traits\HasAdminGrants;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

class MarketplaceProduct extends Model
{
    use HasAdminGrants;
    protected $fillable = [
        'user_id',
        'category_id','title','slug','type','description',
        'support_whatsapp','udemy_url',
        'price','pages_count',
        'game_html',
        'cover_image_path','status','is_featured',
        'submitted_at','reviewed_at','admin_note',
    ];

    public function category()
    {
        return $this->belongsTo(MarketplaceCategory::class, 'category_id');
    }

    public function purchases()
    {
        return $this->hasMany(\App\Models\MarketplacePurchase::class, 'product_id');
    }

    public function assets()
    {
        return $this->hasMany(MarketplaceAsset::class, 'product_id');
    }

    public function vendor()
    {
        return $this->belongsTo(\App\Models\User::class, 'user_id');
    }

    public function scopePublished($q)
    {
        return $q->where('status', 'published');
    }

    protected static function booted(): void
    {
        // Nouvelle couverture : générer sa miniature légère tout de suite
        static::saved(function (MarketplaceProduct $p) {
            if ($p->cover_image_path && ($p->wasRecentlyCreated || $p->wasChanged('cover_image_path'))) {
                try {
                    app(VignetteService::class)->generer($p->cover_image_path, true);
                } catch (\Throwable $e) {
                    Log::warning('Vignette non générée', ['product_id' => $p->id, 'msg' => $e->getMessage()]);
                }
            }
        });
    }

    /** URL de la miniature 400×300 (null si absente : ne jamais servir l'original lourd). */
    public function getVignetteUrlAttribute(): ?string
    {
        return app(VignetteService::class)->url($this->cover_image_path);
    }
}
