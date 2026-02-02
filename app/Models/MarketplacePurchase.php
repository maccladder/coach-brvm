<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MarketplacePurchase extends Model
{
    protected $fillable = [
        'user_id','product_id','amount','currency',
        'provider','provider_ref','status','paid_at'
    ];

    protected $casts = [
        'paid_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function product()
    {
        return $this->belongsTo(MarketplaceProduct::class, 'product_id');
    }

    public function scopePaid($q)
    {
        return $q->where('status', 'paid');
    }
}
