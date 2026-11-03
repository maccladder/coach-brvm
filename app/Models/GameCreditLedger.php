<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GameCreditLedger extends Model
{
    protected $table = 'game_credit_ledger';

    protected $fillable = [
        'user_id',
        'game_slug',
        'kind',
        'delta',
        'reason',
        'payment_reference',
        'amount',
        'note',
        'admin_user_id',
        'admin_label',
    ];

    protected $casts = [
        'delta'  => 'integer',
        'amount' => 'integer',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function adminUser()
    {
        return $this->belongsTo(User::class, 'admin_user_id');
    }
}
