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

    /** Tous les fichiers téléchargeables (0..N), y compris en attente de validation. */
    public function files()
    {
        return $this->hasMany(ProductFile::class, 'product_id')
            ->orderBy('sort_order')
            ->orderBy('id');
    }

    /** Fichiers visibles par les acheteurs : uniquement ceux approuvés. */
    public function approvedFiles()
    {
        return $this->files()->where('status', 'approved');
    }

    /** "Windows et macOS" d'après les plateformes des fichiers (null si aucune renseignée). */
    public function availablePlatformsLabel(): ?string
    {
        $families = $this->approvedFiles->pluck('platform_family')->filter()->unique()->values();

        if ($families->isEmpty()) return null;
        if ($families->count() === 1) return $families->first();

        return $families->slice(0, -1)->implode(', ') . ' et ' . $families->last();
    }

    public function vendor()
    {
        return $this->belongsTo(\App\Models\User::class, 'user_id');
    }

    /** Accès au produit : achat payé OU attribution admin active. Vaut pour tous ses fichiers. */
    public function isAccessibleBy(?User $user): bool
    {
        if (!$user) return false;

        return $user->purchasedProducts()
                ->where('marketplace_products.id', $this->id)
                ->wherePivot('status', 'paid')
                ->exists()
            || $this->isGrantedTo($user);
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
