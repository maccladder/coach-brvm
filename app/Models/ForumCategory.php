<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class ForumCategory extends Model
{
    protected $fillable = ['name', 'slug', 'description', 'icon', 'order'];

    public function topics(): HasMany
    {
        return $this->hasMany(ForumTopic::class);
    }

    public function posts(): HasManyThrough
    {
        return $this->hasManyThrough(ForumPost::class, ForumTopic::class);
    }
}
