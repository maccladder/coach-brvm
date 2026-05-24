<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Donation extends Model
{
    protected $fillable = [
        'reference',
        'donor_name',
        'donor_email',
        'amount',
        'currency',
        'status',
        'meta',
        'confirmed_at',
    ];

    protected $casts = [
        'meta'         => 'array',
        'confirmed_at' => 'datetime',
    ];
}
