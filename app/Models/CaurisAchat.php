<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CaurisAchat extends Model
{
    protected $fillable = [
        'user_id',
        'reference',
        'pack',
        'prix',
        'cauris',
        'status',
        'credited_at',
        'meta',
    ];

    protected $casts = [
        'credited_at' => 'datetime',
        'meta'        => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
