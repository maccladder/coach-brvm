<?php

namespace App\Http\Controllers;

use App\Models\GameScore;
use App\Models\MarketplaceProduct;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

/**
 * Classement du jeu "Questions pour un vrai môgô".
 * Réutilise la table générique game_scores (déjà utilisée par Abidjan Run,
 * partitionnée par product_id) — voir GameScoreController.
 */
class VraiMogoScoreController extends Controller
{
    // Slug du produit marketplace correspondant au jeu (cf. lien de partage dans le HTML du jeu).
    private const PRODUCT_SLUG = 'questions-pour-un-vrai-mogo';

    private const MAX_SCORE = 6000;      // 20 questions x 300 pts max
    private const MAX_CORRECT = 20;
    private const POINTS_PER_CORRECT = 300;
    private const THROTTLE_SECONDS = 30;

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
     * POST /jeux/vrai-mogo/score
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
            'score'   => ['required', 'integer', 'min:0', 'max:' . self::MAX_SCORE],
            'correct' => ['required', 'integer', 'min:0', 'max:' . self::MAX_CORRECT],
        ]);

        $validator->after(function ($validator) use ($request) {
            $score = (int) $request->input('score');
            $correct = (int) $request->input('correct');
            if ($score > $correct * self::POINTS_PER_CORRECT) {
                $validator->errors()->add('score', 'Score incohérent avec le nombre de bonnes réponses.');
            }
        });

        if ($validator->fails()) {
            return response()->json(['error' => 'Données invalides', 'details' => $validator->errors()], 422);
        }

        $data = $validator->validated();
        $user = $request->user();

        // Anti-spam : 1 soumission / 30s par utilisateur pour ce jeu.
        $lastPlayedAt = GameScore::where('user_id', $user->id)
            ->where('product_id', $product->id)
            ->max('created_at');

        if ($lastPlayedAt && now()->diffInSeconds($lastPlayedAt) < self::THROTTLE_SECONDS) {
            return response()->json(['error' => 'Merci de patienter avant de renvoyer un score.'], 422);
        }

        GameScore::create([
            'user_id'         => $user->id,
            'product_id'      => $product->id,
            'score'           => $data['score'],
            'distance'        => 0,
            'coins'           => 0,
            'correct_answers' => $data['correct'],
            'created_at'      => now(),
        ]);

        $best = (int) GameScore::where('user_id', $user->id)
            ->where('product_id', $product->id)
            ->max('score');

        $rank = GameScore::selectRaw('user_id, MAX(score) as best')
            ->where('product_id', $product->id)
            ->groupBy('user_id')
            ->having('best', '>', $best)
            ->count() + 1;

        $total = GameScore::where('product_id', $product->id)
            ->distinct('user_id')
            ->count('user_id');

        return response()->json([
            'rank'  => $rank,
            'total' => $total,
            'best'  => $best,
        ]);
    }

    /**
     * GET /jeux/vrai-mogo/classement
     */
    public function leaderboard(Request $request)
    {
        $product = $this->resolveProduct();
        if (!$product) {
            return response()->json(['error' => 'Jeu non disponible'], 404);
        }

        if (!$this->hasAccess($request, $product)) {
            return response()->json(['error' => 'Accès refusé'], 403);
        }

        $user = $request->user();

        $top = GameScore::selectRaw('user_id, MAX(score) as best_score')
            ->where('product_id', $product->id)
            ->groupBy('user_id')
            ->orderByDesc('best_score')
            ->limit(10)
            ->with('user:id,name')
            ->get()
            ->map(fn ($row) => [
                'name'  => $row->user ? $row->user->name : 'Anonyme',
                'score' => (int) $row->best_score,
                'is_me' => $row->user_id === $user->id,
            ]);

        $myBestScore = GameScore::where('user_id', $user->id)
            ->where('product_id', $product->id)
            ->max('score');

        $me = null;
        if ($myBestScore !== null) {
            $myRank = GameScore::selectRaw('user_id, MAX(score) as best')
                ->where('product_id', $product->id)
                ->groupBy('user_id')
                ->having('best', '>', $myBestScore)
                ->count() + 1;

            $me = ['rank' => $myRank, 'score' => (int) $myBestScore];
        }

        $total = GameScore::where('product_id', $product->id)
            ->distinct('user_id')
            ->count('user_id');

        return response()->json([
            'top'   => $top,
            'me'    => $me,
            'total' => $total,
        ]);
    }
}
