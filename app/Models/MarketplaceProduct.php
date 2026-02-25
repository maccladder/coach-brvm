<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MarketplaceProduct extends Model
{
    protected $fillable = [
        'user_id',
        'category_id','title','slug','type','description',
        'support_whatsapp',
        'price','pages_count', // ✅ AJOUT
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
}
