<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class SGI extends Model
{
    protected $table = 'sgis';

    protected $fillable = [
        'name','slug','country','city','address','po_box',
        'email','phone','phone2','website',
        'source_name','source_url','is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::saving(function (SGI $sgi) {
            if (blank($sgi->slug) && filled($sgi->name)) {
                $sgi->slug = Str::slug($sgi->name);
            }
        });
    }
}
