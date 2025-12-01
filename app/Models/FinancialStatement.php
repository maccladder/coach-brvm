<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FinancialStatement extends Model
{
    protected $fillable = [
        'issuer',
        'period',
        'statement_type',
        'file_path',
        'extracted_metrics',
        'published_at',
    ];

    // ✅ Fusion des deux casts en un seul
    protected $casts = [
        'extracted_metrics' => 'array',
        'published_at' => 'date',
    ];
}
