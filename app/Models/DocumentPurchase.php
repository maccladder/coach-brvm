<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DocumentPurchase extends Model
{
    protected $fillable = [
        'user_id','document_id','amount','currency',
        'provider','provider_ref','status','paid_at'
    ];

    protected $casts = [
        'paid_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function document()
    {
        return $this->belongsTo(Document::class);
    }

    public function scopePaid($q)
    {
        return $q->where('status', 'paid');
    }
}
