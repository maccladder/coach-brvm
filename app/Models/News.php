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
}
