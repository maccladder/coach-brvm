<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ComparateurPrixHistorique extends Model
{
    protected $table = 'comparateur_prix_historique';

    protected $fillable = [
        'id_produit',
        'site',
        'prix',
        'date',
    ];

    protected $casts = [
        'prix' => 'integer',
        'date' => 'date',
    ];
}
