<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VirtualPosition extends Model
{
    protected $fillable = [
        'virtual_wallet_id',
        'ticker',
        'name',
        'qty',
        'avg_price',
    ];

    public function wallet()
    {
        return $this->belongsTo(VirtualWallet::class, 'virtual_wallet_id');
    }
}
