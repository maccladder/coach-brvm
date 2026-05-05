<?php

namespace App\Models;

use App\Traits\HasAdminGrants;
use Illuminate\Database\Eloquent\Model;

class Course extends Model
{
    use HasAdminGrants;

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
