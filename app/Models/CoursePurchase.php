<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CoursePurchase extends Model
{
    protected $fillable = [
        'user_id',
        'course_id',
        'amount_fcfa',
        'status',
        'transaction_id',
        'paid_at',
        'meta',
    ];

    protected $casts = [
        'meta' => 'array',
        'paid_at' => 'datetime',
    ];

    public function course()
    {
        return $this->belongsTo(\App\Models\Course::class);
    }

    public function user()
    {
        return $this->belongsTo(\App\Models\User::class);
    }
}
