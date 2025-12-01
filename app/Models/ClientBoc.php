<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClientBoc extends Model
{
    use HasFactory;

    protected $fillable = [
    'title',
    'boc_date',
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
        'boc_date' => 'date',
    ];
}
