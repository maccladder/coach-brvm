<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Glossaire extends Model
{
    protected $fillable = [
        'lettre',
        'terme',
        'definition',
    ];
}
