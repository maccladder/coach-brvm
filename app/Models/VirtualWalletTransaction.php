<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VirtualWalletTransaction extends Model
{
    protected $fillable = [
        'virtual_wallet_id',
        'type',       // TOPUP / BUY / SELL
        'ticker',
        'qty',
        'price',
        'amount',     // montant total
        'meta',       // json optionnel
    ];

    protected $casts = [
        'meta' => 'array',
    ];

    public function wallet()
    {
        return $this->belongsTo(VirtualWallet::class, 'virtual_wallet_id');
    }
}
