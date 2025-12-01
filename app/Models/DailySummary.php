<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class DailySummary extends Model {

    protected $fillable = [
        'for_date',
        'summary_markdown',
        'signals',
        'avatar_video_url',
    ];
    protected $casts = ['for_date'=>'date','signals'=>'array'];
}
