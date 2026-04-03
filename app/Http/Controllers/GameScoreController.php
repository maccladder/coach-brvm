<?php

namespace App\Http\Controllers;

use App\Models\GameScore;
use App\Models\MarketplaceProduct;
use Illuminate\Http\Request;

class GameScoreController extends Controller
{
    /**
     * POST /api/games/{product}/score
     * Enregistre le score d'une partie — appelé via fetch depuis la page play.
     */
    public function store(Request $request, MarketplaceProduct $product)
    {
        $user = $request->user();

        // Verifier que l'utilisateur a achete le jeu
        $hasAccess = $user->purchasedProducts()
            ->where('marketplace_products.id', $product->id)
            ->wherePivot('status', 'paid')
            ->exists();

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
     * GET /games/{product}/leaderboard
     * Top 20 scores (meilleur score par joueur) + score du joueur connecte.
     */
    public function leaderboard(Request $request, MarketplaceProduct $product)
    {
        abort_unless($product->type === 'game' && $product->status === 'published', 404);

        // Verifier l'acces au jeu
        $user = $request->user();
        $hasAccess = $user
            ? $user->purchasedProducts()
                ->where('marketplace_products.id', $product->id)
                ->wherePivot('status', 'paid')
                ->exists()
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
