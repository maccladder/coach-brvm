<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MarketplaceProduct extends Model
{
    protected $fillable = [
        'category_id','title','slug','type','description',
        'price','cover_image_path','status','is_featured'
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

    public function scopePublished($q)
    {
        return $q->where('status', 'published');
    }
}
