<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VirtualWalletTransaction extends Model
{
    protected $fillable = [
        'user_id', 'type', 'ticker', 'name', 'qty', 'price', 'amount', 'meta', 'motif', 'created_by',
    ];

    protected $casts = [
        'meta' => 'array',
    ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
