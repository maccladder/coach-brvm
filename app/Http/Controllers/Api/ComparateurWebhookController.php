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
            'categorie'             => ['sometimes', 'string'],
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

        if (!$request->has('categorie')) {
            $this->writeAtomic($dir, $path, $data);

            $nbProduits = count($data['produits']);

            Log::info('Comparateur webhook n8n — produits.json mis à jour', [
                'nb_produits' => $nbProduits,
            ]);

            return response()->json([
                'ok'          => true,
                'nb_produits' => $nbProduits,
            ]);
        }

        $categorie = $data['categorie'];

        foreach ($data['produits'] as $produit) {
            if ($produit['categorie'] !== $categorie) {
                return response()->json([
                    'error'   => 'validation_failed',
                    'message' => "Chaque produit doit avoir categorie=\"{$categorie}\".",
                ], 422);
            }
        }

        $existant = [];
        if (File::exists($path)) {
            $decoded  = json_decode(File::get($path), true);
            $existant = is_array($decoded['produits'] ?? null) ? $decoded['produits'] : [];
        }

        $conserves = array_values(array_filter($existant, function ($p) use ($categorie) {
            return ($p['categorie'] ?? null) !== $categorie;
        }));

        $produitsFusionnes = array_merge($conserves, $data['produits']);

        $nouveauContenu = [
            'generated_at' => now()->utc()->format('Y-m-d\TH:i:s\Z'),
            'nb_produits'  => count($produitsFusionnes),
            'produits'     => $produitsFusionnes,
        ];

        $this->writeAtomic($dir, $path, $nouveauContenu);

        $nbProduitsCategorie = count($data['produits']);
        $nbProduitsTotal     = count($produitsFusionnes);

        Log::info('Comparateur webhook n8n — produits.json fusionné par categorie', [
            'categorie'             => $categorie,
            'nb_produits_categorie' => $nbProduitsCategorie,
            'nb_produits_total'     => $nbProduitsTotal,
        ]);

        return response()->json([
            'ok'                    => true,
            'categorie'             => $categorie,
            'nb_produits_categorie' => $nbProduitsCategorie,
            'nb_produits_total'     => $nbProduitsTotal,
        ]);
    }

    private function writeAtomic(string $dir, string $path, array $contenu): void
    {
        $tmpPath = $dir.'/.produits.json.'.uniqid('', true).'.tmp';
        file_put_contents($tmpPath, json_encode($contenu, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        rename($tmpPath, $path);
    }
}
