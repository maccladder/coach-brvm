<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\GameSave;
use App\Models\CaurisAchat;
use App\Models\MarketplaceProduct;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class AdminGarbaMasterController extends Controller
{
    public function index()
    {
        $product = MarketplaceProduct::where('slug', 'garba-master')->first();

        // Utilisateurs ayant claimé le jeu
        $totalClaims = $product
            ? DB::table('marketplace_purchases')
                ->where('product_id', $product->id)
                ->where('status', 'paid')
                ->count()
            : 0;

        // Derniers claims
        $derniersClaims = $product
            ? DB::table('marketplace_purchases as mp')
                ->join('users', 'users.id', '=', 'mp.user_id')
                ->where('mp.product_id', $product->id)
                ->where('mp.status', 'paid')
                ->select('users.name', 'users.email', 'mp.created_at', 'mp.provider')
                ->orderByDesc('mp.created_at')
                ->limit(20)
                ->get()
            : collect();

        // Joueurs avec une save (ont vraiment joué)
        $totalJoueurs = GameSave::count();

        // Répartition par niveau de maquis
        $parNiveau = GameSave::select('niveau_maquis', DB::raw('count(*) as nb'))
            ->groupBy('niveau_maquis')
            ->orderBy('niveau_maquis')
            ->pluck('nb', 'niveau_maquis');

        // Joueurs actifs (save modifiée dans les 7 derniers jours)
        $joueursActifs7j = GameSave::where('updated_at', '>=', now()->subDays(7))->count();
        $joueursActifs30j = GameSave::where('updated_at', '>=', now()->subDays(30))->count();

        // Solde moyen des joueurs
        $soldeMoyen = GameSave::avg('solde');

        // Achats de cauris (revenus in-game)
        $totalAchatsAcceptes = CaurisAchat::where('status', 'ACCEPTED')->count();
        $revenuTotal = CaurisAchat::where('status', 'ACCEPTED')->sum('prix');
        $revenuMoyen = CaurisAchat::where('status', 'ACCEPTED')->avg('prix');

        // Répartition achats par pack
        $achatsPack = CaurisAchat::where('status', 'ACCEPTED')
            ->select('pack', DB::raw('count(*) as nb'), DB::raw('sum(prix) as total'))
            ->groupBy('pack')
            ->get();

        // Derniers achats de cauris
        $derniersAchats = CaurisAchat::with('user')
            ->where('status', 'ACCEPTED')
            ->orderByDesc('credited_at')
            ->limit(15)
            ->get();

        return view('admin.garba-master', compact(
            'totalClaims', 'derniersClaims',
            'totalJoueurs', 'parNiveau',
            'joueursActifs7j', 'joueursActifs30j', 'soldeMoyen',
            'totalAchatsAcceptes', 'revenuTotal', 'revenuMoyen',
            'achatsPack', 'derniersAchats'
        ));
    }
}
