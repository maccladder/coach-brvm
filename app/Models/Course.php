<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Course extends Model
{

     protected $fillable = [
        'title',
        'slug',
        'price_fcfa',
        'description',
        'cf_uid',
        'is_active',
    ];


    public function purchases()
{
    return $this->hasMany(\App\Models\CoursePurchase::class);
}

}
