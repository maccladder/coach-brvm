<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MarketplaceCategory extends Model
{
    protected $fillable = ['name','slug'];

    public function products()
    {
        return $this->hasMany(MarketplaceProduct::class, 'category_id');
    }
}
