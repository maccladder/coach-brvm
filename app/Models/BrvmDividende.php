<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BrvmDividende extends Model
{
    protected $table = 'brvm_dividendes';

    protected $fillable = [
        'ticker',
        'societe',
        'dividende_net',
        'date_paiement',
        'rendement_net',
        'per',
        'boc_date_reference',
        'source_boc',
    ];

    protected $casts = [
        'date_paiement' => 'date',
        'boc_date_reference' => 'date',
        'dividende_net' => 'decimal:2',
        'rendement_net' => 'decimal:2',
        'per' => 'decimal:2',
    ];
}
