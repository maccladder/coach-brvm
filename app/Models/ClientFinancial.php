<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClientFinancial extends Model
{
    use HasFactory;

    protected $fillable = [
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
    ];

    protected $casts = [
        'financial_date' => 'date',
    ];
}
