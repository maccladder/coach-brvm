<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GameSave extends Model
{
    protected $fillable = [
        'user_id',
        'solde',
        'tables_actives',
        'nb_serveuses',
        'niveau_menu',
        'cout_table',
        'cout_serveuse',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
