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

    // ✅ noms réels en DB (et utilisés par daily_bocs)
    'file_path',
    'original_name',

    'daily_boc_id',
    'is_public',

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
