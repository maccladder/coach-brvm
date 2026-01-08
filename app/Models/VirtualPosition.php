<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VirtualPosition extends Model
{
    protected $fillable = ['user_id', 'ticker', 'name', 'qty', 'avg_price'];
}
