<?php

// app/Models/Payment.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    protected $fillable = [
        'user_id','transaction_id','amount_paid','amount_virtual',
        'purpose','status','credited_at','notified_at','meta'
    ];

    protected $casts = [
        'meta' => 'array',
        'credited_at' => 'datetime',
        'notified_at' => 'datetime',
    ];

    public function user()
{
    return $this->belongsTo(\App\Models\User::class);
}




}
