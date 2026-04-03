<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClientFinancial extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'client_email',
        'title',
        'company',
        'period',
        'financial_date',
        'original_filename',
        'stored_path',
        'interpreted_markdown',
        'avatar_video_url',
        'audio_path',
        'amount',
        'status',
        'transaction_id',
        'notified_at',
    ];

    protected $casts = [
        'financial_date' => 'date',
        'notified_at'    => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(\App\Models\User::class);
    }
}
