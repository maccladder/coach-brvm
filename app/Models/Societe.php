<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Societe extends Model
{
    public const LISTED_COUNT_CACHE_KEY = 'societes_listed_count';

    protected static function booted(): void
    {
        $forget = fn () => Cache::forget(self::LISTED_COUNT_CACHE_KEY);

        static::saved($forget);
        static::deleted($forget);
    }

    /**
     * Nombre de sociétés cotées (mis en cache 1 h, invalidé à chaque
     * modification via le modèle).
     */
    public static function listedCount(): int
    {
        return Cache::remember(
            self::LISTED_COUNT_CACHE_KEY,
            3600,
            fn () => static::where('is_listed', true)->count()
        );
    }

    protected $fillable = [
        'code',
        'name',
        'sector',
        'country',
        'is_listed',
        'listing_date',
    ];

    public function financialReports()
    {
        return $this->hasMany(FinancialReport::class);
    }
}
