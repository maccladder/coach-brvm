<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VirtualWallet extends Model
{
    protected $fillable = [
        'user_id',
        'balance',
    ];

    // (optionnel) si ta table ne s'appelle pas virtual_wallets :
    // protected $table = 'virtual_wallets';

    public function positions()
    {
        return $this->hasMany(VirtualPosition::class);
    }

    public function transactions()
    {
        return $this->hasMany(VirtualWalletTransaction::class);
    }
}
