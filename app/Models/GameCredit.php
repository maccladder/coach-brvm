<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GameCredit extends Model
{
    protected $fillable = [
        'user_id',
        'game_slug',
        'kind',
        'balance',
    ];

    protected $casts = [
        'balance' => 'integer',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
