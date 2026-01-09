<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Book extends Model
{
    protected $fillable = [
        'title','slug','description','is_free','is_published','estimated_minutes'
    ];

    public function pages()
    {
        return $this->hasMany(BookPage::class)->orderBy('page_no');
    }
}
