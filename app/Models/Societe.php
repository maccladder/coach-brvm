<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Societe extends Model
{
    protected $fillable = [
        'code',
        'name',
        'sector',
        'country',
        'is_listed',
        'listing_date',
    ];

    public function financialReports()
    {
        return $this->hasMany(FinancialReport::class);
    }
}
