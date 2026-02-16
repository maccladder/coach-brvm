<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AdminNotification extends Model
{
    protected $fillable = ['type','title','message','url','read_at'];

    protected $casts = [
        'read_at' => 'datetime',
    ];

    public function scopeUnread($q) { return $q->whereNull('read_at'); }
}
