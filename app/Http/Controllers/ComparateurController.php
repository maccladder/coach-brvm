<?php

namespace App\Http\Controllers;

use App\Models\ComparateurPrixHistorique;

class ComparateurController extends Controller
{
    public function index()
    {
        return view('comparateur');
    }

    public function data()
    {
        $path = storage_path('app/comparateur/produits.json');

        if (!file_exists($path)) {
            abort(404);
        }

        $contenu = json_decode(file_get_contents($path), true);

        if (is_array($contenu) && !empty($contenu['produits'])) {
            $contenu['produits'] = $this->avecHistorique($contenu['produits']);
        }

        return response()->json($contenu, 200, [
            'Cache-Control' => 'no-store',
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    /**
     * Attache à chaque produit son historique de prix des 30 derniers jours
     * (utilisé côté vue pour les produits à offre unique).
     */
    private function avecHistorique(array $produits): array
    {
        $ids = array_column($produits, 'id');

        $historiques = ComparateurPrixHistorique::query()
            ->whereIn('id_produit', $ids)
            ->where('date', '>=', now()->subDays(30)->toDateString())
            ->orderBy('date')
            ->get(['id_produit', 'site', 'prix', 'date'])
            ->groupBy('id_produit');

        foreach ($produits as &$produit) {
            $lignes = $historiques->get($produit['id']);

            $produit['historique'] = $lignes
                ? $lignes->map(fn ($l) => [
                    'site' => $l->site,
                    'prix' => $l->prix,
                    'date' => $l->date->toDateString(),
                ])->values()->all()
                : [];
        }

        return $produits;
    }
}
