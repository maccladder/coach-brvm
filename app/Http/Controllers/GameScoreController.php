<?php

namespace App\Http\Controllers;

use App\Models\GamePremiumUnlock;
use App\Models\GameScore;
use App\Models\MarketplaceProduct;
use App\Services\PaystackService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class GameScoreController extends Controller
{
    // Prix (en XOF) autorisés par feature déverrouillable, par produit — le client ne choisit
    // jamais le prix, seulement quelle feature il veut acheter. Évite qu'un joueur falsifie le montant.
    private const FEATURE_PRICES = [
        'abidjan-run-le-jeu-run-a-livoirienne' => [
            'premium_chars' => 1000,
        ],
        'roi-du-cacao' => [
            'turbo'    => 500,
            'planteur' => 1000,
            'magnat'   => 2500,
        ],
        'roi-du-cacao-le-jeu-ivoirien-addictif' => [
            'turbo'    => 500,
            'planteur' => 1000,
            'magnat'   => 2500,
        ],
    ];

    private function resolveFeaturePrice(MarketplaceProduct $product, string $feature): ?int
    {
        return self::FEATURE_PRICES[$product->slug][$feature] ?? null;
    }

    /**
     * POST /api/games/{product}/score
     * Enregistre le score d'une partie — appelé via fetch depuis la page play.
     */
    public function store(Request $request, MarketplaceProduct $product)
    {
        $user = $request->user();

        // Verifier que l'utilisateur a achete ou recu le jeu
        $hasAccess = $user->purchasedProducts()
            ->where('marketplace_products.id', $product->id)
            ->wherePivot('status', 'paid')
            ->exists()
            || $product->isGrantedTo($user);

        if (!$hasAccess) {
            return response()->json(['error' => 'Acces refuse'], 403);
        }

        if ($product->type !== 'game') {
            return response()->json(['error' => 'Produit invalide'], 422);
        }

        $data = $request->validate([
            'score'    => ['required', 'integer', 'min:0', 'max:9999999'],
            'distance' => ['required', 'integer', 'min:0', 'max:9999999'],
            'coins'    => ['required', 'integer', 'min:0', 'max:9999999'],
        ]);

        // Sauvegarder la partie (chaque partie = une ligne)
        $entry = GameScore::create([
            'user_id'    => $user->id,
            'product_id' => $product->id,
            'score'      => $data['score'],
            'distance'   => $data['distance'],
            'coins'      => $data['coins'],
            'created_at' => now(),
        ]);

        // Meilleur score personnel
        $best = GameScore::where('user_id', $user->id)
            ->where('product_id', $product->id)
            ->max('score');

        // Rang global (combien de joueurs distincts ont un meilleur best score)
        $rank = GameScore::selectRaw('user_id, MAX(score) as best')
            ->where('product_id', $product->id)
            ->groupBy('user_id')
            ->having('best', '>', $data['score'])
            ->count() + 1;

        return response()->json([
            'saved'      => true,
            'best_score' => $best,
            'rank'       => $rank,
            'is_new_best'=> $data['score'] >= $best,
        ]);
    }

    /**
     * POST /games/{product}/verify-premium
     * Vérifie un paiement Paystack in-game et enregistre le déverrouillage premium.
     */
    public function verifyPremiumUnlock(Request $request, MarketplaceProduct $product, PaystackService $paystack)
    {
        $user = $request->user();

        // L'utilisateur doit avoir acheté ou reçu le jeu
        $hasAccess = $user->purchasedProducts()
            ->where('marketplace_products.id', $product->id)
            ->wherePivot('status', 'paid')
            ->exists()
            || $product->isGrantedTo($user);

        if (!$hasAccess) {
            return response()->json(['error' => 'Accès refusé'], 403);
        }

        if ($product->type !== 'game') {
            return response()->json(['error' => 'Produit invalide'], 422);
        }

        $request->validate([
            'ref'     => ['required', 'string', 'max:100'],
            'feature' => ['sometimes', 'string', 'max:50'],
        ]);

        $ref     = (string) $request->input('ref');
        $feature = (string) ($request->input('feature', 'premium_chars'));

        if ($this->resolveFeaturePrice($product, $feature) === null) {
            return response()->json(['error' => 'Fonctionnalité inconnue'], 422);
        }

        // Déjà déverrouillé → renvoyer directement
        $existing = GamePremiumUnlock::where('user_id', $user->id)
            ->where('product_id', $product->id)
            ->where('feature', $feature)
            ->first();

        if ($existing) {
            return response()->json(['unlocked' => true, 'already' => true]);
        }

        // Vérification Paystack côté serveur (la clé secrète ne quitte jamais le serveur)
        $data = $paystack->verify($ref);

        if (!$data) {
            Log::warning('GamePremiumUnlock: Paystack verify failed', [
                'user_id'    => $user->id,
                'product_id' => $product->id,
                'ref'        => $ref,
            ]);
            return response()->json(['error' => 'Vérification Paystack échouée'], 402);
        }

        if (($data['status'] ?? null) !== 'success') {
            return response()->json(['error' => 'Paiement non validé : ' . ($data['status'] ?? 'unknown')], 402);
        }

        // Montant reçu (Paystack retourne en kobo-like, /100 pour XOF)
        $amountPaid = (int) (($data['amount'] ?? 0) / 100);

        // Enregistrement (ignoré si doublon de ref grâce au unique index)
        try {
            GamePremiumUnlock::create([
                'user_id'     => $user->id,
                'product_id'  => $product->id,
                'feature'     => $feature,
                'paystack_ref'=> $ref,
                'amount'      => $amountPaid,
                'unlocked_at' => now(),
            ]);
        } catch (\Illuminate\Database\UniqueConstraintViolationException $e) {
            // Ref ou (user, product, feature) déjà présent — OK quand même
        }

        Log::info('GamePremiumUnlock: unlocked', [
            'user_id'    => $user->id,
            'product_id' => $product->id,
            'feature'    => $feature,
            'amount'     => $amountPaid,
            'ref'        => $ref,
        ]);

        return response()->json(['unlocked' => true, 'amount' => $amountPaid]);
    }

    /**
     * POST /games/{product}/init-premium-payment
     * Initialise une transaction Paystack côté serveur et retourne l'authorization_url.
     */
    public function initPremiumPayment(Request $request, MarketplaceProduct $product, PaystackService $paystack)
    {
        $user = $request->user();

        $hasAccess = $user
            ? ($user->purchasedProducts()
                ->where('marketplace_products.id', $product->id)
                ->wherePivot('status', 'paid')
                ->exists()
                || $product->isGrantedTo($user))
            : false;

        if (!$hasAccess) {
            return response()->json(['error' => 'Accès refusé'], 403);
        }

        if ($product->type !== 'game') {
            return response()->json(['error' => 'Produit invalide'], 422);
        }

        // Nom générique "feature", avec compat sur l'ancien champ "char" (Abidjan Run).
        $feature = (string) $request->input('feature', $request->input('char', 'premium_chars'));

        $price = $this->resolveFeaturePrice($product, $feature);
        if ($price === null) {
            return response()->json(['error' => 'Fonctionnalité inconnue'], 422);
        }

        // Déjà déverrouillé → pas besoin de payer
        $existing = GamePremiumUnlock::where('user_id', $user->id)
            ->where('product_id', $product->id)
            ->where('feature', $feature)
            ->first();

        if ($existing) {
            return response()->json(['already_unlocked' => true]);
        }

        $ref = 'GAME' . $product->id . '_' . strtoupper($feature) . '_' . round(microtime(true) * 1000);

        $url = $paystack->initialize([
            'email'        => $user->email,
            'amount'       => $price, // en XOF (le service multiplie par 100 pour Paystack)
            'currency'     => 'XOF',
            'reference'    => $ref,
            'callback_url' => route('games.premium-callback', $product),
            'metadata'     => [
                'product_id' => $product->id,
                'feature'    => $feature,
                'user_id'    => $user->id,
            ],
        ]);

        if (!$url) {
            return response()->json(['error' => 'Erreur initialisation paiement'], 500);
        }

        return response()->json(['url' => $url]);
    }

    /**
     * GET /games/{product}/premium-callback
     * Callback Paystack pour mobile (redirect flow) — vérifie et sauvegarde le déverrouillage.
     */
    public function premiumCallback(Request $request, MarketplaceProduct $product, PaystackService $paystack)
    {
        $user = $request->user();

        $hasAccess = $user
            ? ($user->purchasedProducts()
                ->where('marketplace_products.id', $product->id)
                ->wherePivot('status', 'paid')
                ->exists()
                || $product->isGrantedTo($user))
            : false;

        if (!$hasAccess) {
            return redirect()->route('my.products.play', $product);
        }

        $reference = (string) $request->query('reference', $request->query('trxref', ''));

        if ($reference) {
            $data = $paystack->verify($reference);

            if ($data && ($data['status'] ?? null) === 'success') {
                // La feature achetée vient des métadonnées enregistrées côté serveur à l'initialisation
                // (jamais du client), donc pas de risque de spoof.
                $feature = (string) ($data['metadata']['feature'] ?? 'premium_chars');

                $existing = GamePremiumUnlock::where('user_id', $user->id)
                    ->where('product_id', $product->id)
                    ->where('feature', $feature)
                    ->first();

                if ($this->resolveFeaturePrice($product, $feature) !== null && !$existing) {
                    $amountPaid = (int) (($data['amount'] ?? 0) / 100);

                    try {
                        GamePremiumUnlock::create([
                            'user_id'      => $user->id,
                            'product_id'   => $product->id,
                            'feature'      => $feature,
                            'paystack_ref' => $reference,
                            'amount'       => $amountPaid,
                            'unlocked_at'  => now(),
                        ]);

                        Log::info('GamePremiumUnlock (mobile callback): unlocked', [
                            'user_id'    => $user->id,
                            'product_id' => $product->id,
                            'ref'        => $reference,
                            'amount'     => $amountPaid,
                        ]);
                    } catch (\Illuminate\Database\UniqueConstraintViolationException $e) {
                        // déjà enregistré
                    }
                }
            }
        }

        // Redirige vers la page de jeu — le contrôleur play() relira l'unlock en DB
        return redirect()->route('my.products.play', $product);
    }

    /**
     * GET /games/{product}/leaderboard
     * Top 20 scores (meilleur score par joueur) + score du joueur connecte.
     */
    public function leaderboard(Request $request, MarketplaceProduct $product)
    {
        abort_unless($product->type === 'game' && $product->status === 'published', 404);

        // Verifier l'acces au jeu
        $user = $request->user();
        $hasAccess = $user
            ? ($user->purchasedProducts()
                ->where('marketplace_products.id', $product->id)
                ->wherePivot('status', 'paid')
                ->exists()
                || $product->isGrantedTo($user))
            : false;

        abort_unless($hasAccess, 403);

        // Top 20 : meilleur score par joueur
        $top = GameScore::selectRaw('user_id, MAX(score) as best_score, MAX(distance) as best_distance, COUNT(*) as games_played, MAX(created_at) as last_played')
            ->where('product_id', $product->id)
            ->groupBy('user_id')
            ->orderByDesc('best_score')
            ->limit(20)
            ->with('user:id,name')
            ->get()
            ->map(function ($row, $index) {
                return [
                    'rank'         => $index + 1,
                    'user_id'      => $row->user_id,
                    'name'         => $row->user ? $row->user->name : 'Anonyme',
                    'best_score'   => (int) $row->best_score,
                    'best_distance'=> (int) $row->best_distance,
                    'games_played' => (int) $row->games_played,
                    'last_played'  => $row->last_played,
                ];
            });

        // Score personnel du joueur connecte (hors top 20 si non classe)
        $myBest = null;
        $myRank = null;
        if ($user) {
            $myBestScore = GameScore::where('user_id', $user->id)
                ->where('product_id', $product->id)
                ->max('score');

            if ($myBestScore !== null) {
                $myRank = GameScore::selectRaw('user_id, MAX(score) as best')
                    ->where('product_id', $product->id)
                    ->groupBy('user_id')
                    ->having('best', '>', $myBestScore)
                    ->count() + 1;

                $myBest = [
                    'rank'       => $myRank,
                    'best_score' => (int) $myBestScore,
                    'games_played' => GameScore::where('user_id', $user->id)
                        ->where('product_id', $product->id)
                        ->count(),
                ];
            }
        }

        return view('game-leaderboard', compact('product', 'top', 'myBest', 'myRank', 'user'));
    }
}
