<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GameProgress extends Model
{
    protected $table = 'game_progress';

    protected $fillable = [
        'user_id',
        'product_id',
        'data',
    ];

    protected $casts = [
        'data'              => 'array',
        'free_hint_used_at' => 'datetime',
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
