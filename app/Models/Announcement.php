<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

class Announcement extends Model
{
    protected $fillable = [
        'title',
        'excerpt',
        'content',
        'attachment_path',
        'attachment_type',
        'is_published',
        'published_at',
    ];

    protected $casts = [
        'is_published' => 'boolean',
        'published_at' => 'datetime',
    ];

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

    public function getAttachmentUrlAttribute(): ?string
    {
        if (!$this->attachment_path) return null;
        // stockage: storage/app/public/...
        return asset('storage/' . ltrim($this->attachment_path, '/'));
    }
}
