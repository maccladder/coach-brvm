<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class News extends Model
{
    protected $table = 'news';

    protected $fillable = [
        'title',
        'slug',
        'resume',
        'source_name',
        'source_url',
        'impact',
        'categorie',
        'societes',
        'mots_cles',
        'is_published',
        'published_at',
    ];

    protected $casts = [
        'societes'     => 'array',
        'mots_cles'    => 'array',
        'is_published' => 'boolean',
        'published_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (News $news) {
            if (empty($news->slug)) {
                $news->slug = static::generateUniqueSlug($news->title);
            }
        });
    }

    protected static function generateUniqueSlug(string $title): string
    {
        $base = Str::slug($title);
        $slug = $base;
        $i = 2;

        while (static::where('slug', $slug)->exists()) {
            $slug = "{$base}-{$i}";
            $i++;
        }

        return $slug;
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query
            ->where('is_published', true)
            ->where(function ($q) {
                $q->whereNull('published_at')
                  ->orWhere('published_at', '<=', Carbon::now());
            });
    }

    public function getPublicDateAttribute(): string
    {
        $dt = $this->published_at ?? $this->created_at;
        return $dt?->format('d/m/Y') ?? '';
    }

    /** Date de publication effective (published_at, sinon created_at). */
    public function datePublication(): ?Carbon
    {
        return $this->published_at ?? $this->created_at;
    }

    /** Ordre « plus récent d'abord », cohérent avec la page /actualites. */
    public function scopeRecentFirst(Builder $query): Builder
    {
        return $query
            ->orderByRaw('COALESCE(published_at, created_at) DESC')
            ->orderByDesc('id');
    }

    /**
     * Bloc « À la une » de l'accueil. Vedette : l'article d'impact « Élevé »
     * le plus récent des dernières 24 h, sinon simplement le dernier publié
     * (la vedette tourne donc chaque jour). Suivent les articles les plus
     * récents, vedette exclue.
     *
     * @return array{vedette: ?News, suivants: \Illuminate\Support\Collection<int, News>}
     */
    public static function aLaUne(int $nbSuivants = 3): array
    {
        $vedette = static::published()
            ->where('impact', 'Élevé')
            ->whereRaw('COALESCE(published_at, created_at) >= ?', [now()->subDay()->toDateTimeString()])
            ->recentFirst()
            ->first()
            ?? static::published()->recentFirst()->first();

        $suivants = $vedette
            ? static::published()->whereKeyNot($vedette->getKey())->recentFirst()->limit($nbSuivants)->get()
            : collect();

        return ['vedette' => $vedette, 'suivants' => $suivants];
    }
}
