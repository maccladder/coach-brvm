<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
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

    /**
     * Exclut les tickers marqués radiés dans societes (is_listed = false).
     * Un ticker absent de societes (ex. nouvelle cotation pas encore saisie)
     * reste visible.
     */
    public function scopeExcludingDelisted(Builder $query): Builder
    {
        return $query->whereNotIn(
            $query->qualifyColumn('ticker'),
            Societe::select('code')->where('is_listed', false)
        );
    }
}
