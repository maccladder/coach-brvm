<?php

namespace App\Models;

use App\Models\Societe;
use Illuminate\Database\Eloquent\Model;

class FinancialReport extends Model
{
    protected $fillable = [
        'societe_id',
        'year',
        'period',
        'status',
        'file_path',
        'published_at',
        'uploaded_by',
    ];

    protected $casts = [
        'year' => 'integer',
        'published_at' => 'date',
    ];

    public const PERIODS = ['Q1', 'S1', 'Q3', 'FY'];
    public const STATUSES = ['pending', 'published', 'not_published'];

    public function societe()
    {
        return $this->belongsTo(Societe::class);
    }
}
