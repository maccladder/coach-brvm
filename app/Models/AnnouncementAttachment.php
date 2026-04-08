<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AnnouncementAttachment extends Model
{
    protected $fillable = ['announcement_id', 'path', 'type', 'original_name', 'sort'];

    public function announcement(): BelongsTo
    {
        return $this->belongsTo(Announcement::class);
    }

    public function getUrlAttribute(): string
    {
        return asset('storage/' . ltrim($this->path, '/'));
    }

    public function getIsImageAttribute(): bool
    {
        return $this->type === 'image';
    }

    public function getIsDocAttribute(): bool
    {
        return in_array($this->type, ['pdf', 'doc']);
    }
}
