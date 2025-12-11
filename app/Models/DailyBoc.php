<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;

class DailyBoc extends Model
{
    protected $table = 'daily_bocs';

    protected $fillable = [
        'date_boc',
        'file_path',
        'original_name',
    ];

    /**
     * Convertir automatiquement date_boc en objet Carbon
     */
    protected function casts(): array
    {
        return [
            'date_boc' => 'date',
        ];
    }
}
