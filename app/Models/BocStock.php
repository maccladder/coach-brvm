<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BocStock extends Model
{
    protected $table = 'boc_stocks';

    protected $fillable = [
        'daily_boc_id',
        'date_boc',
        'ticker',
        'name',
        'price',
        'change',
    ];

    protected $casts = [
        'date_boc' => 'date',
        'price'    => 'float',
        'change'   => 'float',
    ];
}
