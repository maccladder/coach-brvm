<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VirtualWalletTransaction extends Model
{
   protected $fillable = ['user_id', 'type', 'ticker', 'qty', 'price', 'amount', 'meta'];


    protected $casts = [
        'meta' => 'array',
    ];
}
