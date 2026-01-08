<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VirtualWallet extends Model
{
    protected $fillable = ['user_id', 'balance'];

    public function transactions()
    {
        return $this->hasMany(VirtualWalletTransaction::class, 'user_id', 'user_id')
            ->latest();
    }
}
