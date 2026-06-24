<?php

namespace App\Http\Controllers;

use App\Models\GameSave;
use Illuminate\Http\Request;

class GameSaveController extends Controller
{
    /**
     * GET /api/jeu/load
     * Renvoie la sauvegarde de l'utilisateur (ou les valeurs par défaut à la première visite).
     */
    public function load(Request $request)
    {
        $user = $request->user();

        $save = GameSave::updateOrCreate(
            ['user_id' => $user->id],
            []   // ne met rien à jour si déjà existant
        );

        return response()->json([
            'solde'         => $save->solde,
            'tables_actives' => $save->tables_actives,
            'nb_serveuses'  => $save->nb_serveuses,
            'niveau_menu'   => $save->niveau_menu,
            'niveau_maquis' => $save->niveau_maquis,
            'cout_table'    => $save->cout_table,
            'cout_serveuse' => $save->cout_serveuse,
        ]);
    }

    /**
     * POST /api/jeu/save
     * Met à jour la sauvegarde de l'utilisateur.
     */
    public function save(Request $request)
    {
        $user = $request->user();

        $data = $request->validate([
            'solde'          => ['required', 'integer', 'min:0'],
            'tables_actives' => ['required', 'integer', 'min:1', 'max:8'],
            'nb_serveuses'   => ['required', 'integer', 'min:1', 'max:5'],
            'niveau_menu'    => ['required', 'integer', 'min:1', 'max:4'],
            'niveau_maquis'  => ['required', 'integer', 'min:1', 'max:3'],
            'cout_table'     => ['required', 'integer', 'min:0'],
            'cout_serveuse'  => ['required', 'integer', 'min:0'],
        ]);

        GameSave::updateOrCreate(
            ['user_id' => $user->id],
            $data
        );

        return response()->json(['ok' => true]);
    }

    /**
     * POST /api/jeu/reset
     * Remet la game_save à zéro (nouvelle partie). Ne touche pas aux cauris_achats.
     */
    public function reset(Request $request)
    {
        $user = $request->user();

        GameSave::updateOrCreate(
            ['user_id' => $user->id],
            [
                'solde'          => 0,
                'tables_actives' => 2,
                'nb_serveuses'   => 1,
                'niveau_menu'    => 1,
                'niveau_maquis'  => 1,
                'cout_table'     => 2000,
                'cout_serveuse'  => 3000,
            ]
        );

        return response()->json(['ok' => true]);
    }
}
