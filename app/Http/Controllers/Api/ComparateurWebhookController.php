<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class ComparateurWebhookController extends Controller
{
    public function store(Request $request)
    {
        $provided = (string) $request->header('X-CLE');
        $expected = (string) config('services.comparateur.cle');

        if ($expected === '' || !hash_equals($expected, $provided)) {
            return response()->json(['error' => 'unauthorized'], 403);
        }

        $validator = Validator::make($request->all(), [
            'generated_at'          => ['required', 'string'],
            'produits'              => ['required', 'array', 'min:1'],
            'produits.*.id'         => ['required', 'string'],
            'produits.*.nom'        => ['required', 'string'],
            'produits.*.categorie'  => ['required', 'string'],
            'produits.*.offres'                 => ['required', 'array', 'min:1'],
            'produits.*.offres.*.site'          => ['required', 'string'],
            'produits.*.offres.*.prix'          => ['required', 'numeric'],
            'produits.*.offres.*.lien'          => ['required', 'string'],
        ], [
            'required' => 'Le champ :attribute est requis.',
            'array'    => 'Le champ :attribute doit être un tableau.',
            'min'      => 'Le champ :attribute ne peut pas être vide.',
            'numeric'  => 'Le champ :attribute doit être numérique.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error'   => 'validation_failed',
                'message' => 'Le corps envoyé ne respecte pas le format attendu.',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $data = $validator->validated();

        $dir  = storage_path('app/comparateur');
        $path = $dir.'/produits.json';

        File::ensureDirectoryExists($dir);

        $tmpPath = $dir.'/.produits.json.'.uniqid('', true).'.tmp';
        file_put_contents($tmpPath, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        rename($tmpPath, $path);

        $nbProduits = count($data['produits']);

        Log::info('Comparateur webhook n8n — produits.json mis à jour', [
            'nb_produits' => $nbProduits,
        ]);

        return response()->json([
            'ok'          => true,
            'nb_produits' => $nbProduits,
        ]);
    }
}
