<?php

namespace App\Http\Controllers;

use App\Models\GameScore;
use App\Models\MarketplaceProduct;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Validator;

/**
 * Score et classement de "GRI-GRI — La Danse des Perles".
 * Réutilise la table générique game_scores (partitionnée par product_id) — voir GameScoreController.
 * Le jeu poste { score, level, coins, final } par postMessage à chaque fin de niveau et au game over ;
 * la page parente (resources/views/gri-gri-play.blade.php) relaie vers store() en fetch.
 */
class GriGriScoreController extends Controller
{
    private const PRODUCT_SLUG = 'gri-gri-la-danse-des-perles';

    private const MAX_LEVEL = 300;

    // Plafonds heuristiques (score/cauris cumulés depuis le début de la partie jusqu'au niveau atteint) :
    // marge généreuse pour ne pas bloquer un joueur habile, mais rejette les valeurs aberrantes.
    private const BASE_MAX_SCORE = 6000;
    private const SCORE_PER_LEVEL = 7000;
    private const BASE_MAX_COINS = 500;
    private const COINS_PER_LEVEL = 400;

    // Deux fenêtres distinctes : les fins de niveau (final:false) sont fréquentes et throttlées
    // plus fort ; un score final:true (game over ou "Quitter la partie") doit presque toujours
    // passer même s'il arrive juste après une fin de niveau, donc fenêtre courte et clé séparée.
    private const THROTTLE_LEVEL_SECONDS = 5;
    private const THROTTLE_FINAL_SECONDS = 2;

    private function resolveProduct(): ?MarketplaceProduct
    {
        return MarketplaceProduct::where('slug', self::PRODUCT_SLUG)
            ->where('type', 'game')
            ->first();
    }

    private function hasAccess(Request $request, MarketplaceProduct $product): bool
    {
        $user = $request->user();

        return $user->purchasedProducts()
            ->where('marketplace_products.id', $product->id)
            ->wherePivot('status', 'paid')
            ->exists()
            || $product->isGrantedTo($user);
    }

    /**
     * POST /jeux/gri-gri/score
     */
    public function store(Request $request)
    {
        $product = $this->resolveProduct();
        if (!$product) {
            return response()->json(['error' => 'Jeu non disponible'], 404);
        }

        if (!$this->hasAccess($request, $product)) {
            return response()->json(['error' => 'Accès refusé'], 403);
        }

        $validator = Validator::make($request->all(), [
            'score' => ['required', 'integer', 'min:0', 'max:9999999'],
            'level' => ['required', 'integer', 'min:1', 'max:' . self::MAX_LEVEL],
            'coins' => ['required', 'integer', 'min:0', 'max:9999999'],
            'final' => ['sometimes', 'boolean'],
        ]);

        $validator->after(function ($validator) use ($request) {
            $level = (int) $request->input('level');

            $maxScore = self::BASE_MAX_SCORE + $level * self::SCORE_PER_LEVEL;
            if ((int) $request->input('score') > $maxScore) {
                $validator->errors()->add('score', 'Score incohérent avec le niveau atteint.');
            }

            $maxCoins = self::BASE_MAX_COINS + $level * self::COINS_PER_LEVEL;
            if ((int) $request->input('coins') > $maxCoins) {
                $validator->errors()->add('coins', 'Cauris incohérents avec le niveau atteint.');
            }
        });

        if ($validator->fails()) {
            return response()->json(['error' => 'Données invalides', 'details' => $validator->errors()], 422);
        }

        $data = $validator->validated();
        $user = $request->user();

        // Anti-spam : throttle court par utilisateur, avec une fenêtre séparée pour les scores
        // finaux (game over / quitter la partie) afin qu'un final:true juste après une fin de
        // niveau ne soit pas rejeté par le throttle des fins de niveau.
        $isFinal = (bool) ($data['final'] ?? false);
        $throttleKey = $isFinal
            ? 'grigri_score_throttle_final_' . $user->id
            : 'grigri_score_throttle_level_' . $user->id;
        $throttleSeconds = $isFinal ? self::THROTTLE_FINAL_SECONDS : self::THROTTLE_LEVEL_SECONDS;

        if (Cache::has($throttleKey)) {
            return response()->json(['error' => 'Merci de patienter avant de renvoyer un score.'], 422);
        }
        Cache::put($throttleKey, true, $throttleSeconds);

        GameScore::create([
            'user_id'    => $user->id,
            'product_id' => $product->id,
            'score'      => $data['score'],
            'distance'   => 0,
            'coins'      => $data['coins'],
            'level'      => $data['level'],
            'created_at' => now(),
        ]);

        $bestScore = (int) GameScore::where('user_id', $user->id)
            ->where('product_id', $product->id)
            ->max('score');

        $bestLevel = (int) GameScore::where('user_id', $user->id)
            ->where('product_id', $product->id)
            ->max('level');

        $rank = GameScore::selectRaw('user_id, MAX(score) as best')
            ->where('product_id', $product->id)
            ->groupBy('user_id')
            ->having('best', '>', $bestScore)
            ->count() + 1;

        return response()->json([
            'saved'       => true,
            'best_score'  => $bestScore,
            'best_level'  => $bestLevel,
            'rank'        => $rank,
            'is_new_best' => $data['score'] >= $bestScore,
        ]);
    }

    /**
     * GET /jeux/gri-gri/classement
     */
    public function leaderboard(Request $request)
    {
        $product = $this->resolveProduct();
        abort_unless($product && $product->status === 'published', 404);

        $user = $request->user();
        abort_unless($user && $this->hasAccess($request, $product), 403);

        $top = GameScore::selectRaw('user_id, MAX(score) as best_score, MAX(level) as best_level, COUNT(*) as games_played, MAX(created_at) as last_played')
            ->where('product_id', $product->id)
            ->groupBy('user_id')
            ->orderByDesc('best_score')
            ->limit(20)
            ->with('user:id,name')
            ->get()
            ->map(function ($row, $index) {
                return [
                    'rank'          => $index + 1,
                    'user_id'       => $row->user_id,
                    'name'          => $row->user ? $row->user->name : 'Anonyme',
                    'best_score'    => (int) $row->best_score,
                    // Réutilise la colonne "distance" attendue par la vue partagée pour afficher le niveau max.
                    'best_distance' => (int) $row->best_level,
                    'games_played'  => (int) $row->games_played,
                    'last_played'   => $row->last_played,
                ];
            });

        $myBest = null;
        $myRank = null;

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
                'rank'         => $myRank,
                'best_score'   => (int) $myBestScore,
                'games_played' => GameScore::where('user_id', $user->id)
                    ->where('product_id', $product->id)
                    ->count(),
            ];
        }

        return view('game-leaderboard', [
            'product'              => $product,
            'top'                  => $top,
            'myBest'               => $myBest,
            'myRank'               => $myRank,
            'user'                 => $user,
            'secondaryMetricLabel' => 'Niveau',
            'secondaryMetricUnit'  => '',
        ]);
    }
}
