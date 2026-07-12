<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GameScore extends Model
{
    public $timestamps = false; // on gere created_at manuellement via useCurrent()

    protected $fillable = [
        'user_id',
        'product_id',
        'score',
        'distance',
        'coins',
        'correct_answers',
        'level',
        'created_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
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
