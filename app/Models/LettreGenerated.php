<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LettreGenerated extends Model
{
    protected $fillable = [
        'user_id',
        'template_slug',
        'title',
        'inputs',
        'content_generated',
        'content_final',
        'status',
        'error_message',
    ];

    protected $casts = [
        'inputs' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getTemplateAttribute(): ?array
    {
        return config("lettreci_templates.types.{$this->template_slug}");
    }

    public function getContentAttribute(): ?string
    {
        return $this->content_final ?? $this->content_generated;
    }
}
