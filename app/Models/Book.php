<?php

namespace App\Models;

use App\Traits\HasAdminGrants;
use Illuminate\Database\Eloquent\Model;

class Book extends Model
{
    use HasAdminGrants;
    protected $fillable = [
        'title','slug','description','is_free','is_published','estimated_minutes'
    ];

    public function pages()
    {
        return $this->hasMany(BookPage::class)->orderBy('page_no');
    }
}
