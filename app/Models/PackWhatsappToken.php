<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PackWhatsappToken extends Model
{
    protected $fillable = ['user_id', 'token', 'used', 'used_at'];

    protected $casts = [
        'used'    => 'boolean',
        'used_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
