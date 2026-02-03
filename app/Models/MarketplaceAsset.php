<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MarketplaceAsset extends Model
{
    protected $fillable = [
        'product_id', 'kind', 'path', 'url', 'label', 'is_downloadable',
    ];

    protected $casts = [
        'is_downloadable' => 'boolean',
    ];

    public function product()
    {
        return $this->belongsTo(MarketplaceProduct::class, 'product_id');
    }
}
