<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GamePremiumUnlock extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'user_id', 'product_id', 'feature', 'paystack_ref', 'amount', 'unlocked_at',
    ];

    protected $casts = [
        'unlocked_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function product()
    {
        return $this->belongsTo(MarketplaceProduct::class, 'product_id');
    }
}
