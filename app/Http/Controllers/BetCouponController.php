<?php

namespace App\Http\Controllers;

use App\Models\BetCoupon;
use Illuminate\Http\Request;

class BetCouponController extends Controller
{
    public function index()
    {
        $ordre = ['sur' => 0, 'equilibre' => 1, 'jackpot' => 2, 'crazy' => 3, 'goals' => 4, 'pepite05' => 5];
        $coupons = BetCoupon::publies()->duJour()->get()
            ->reject(fn ($c) => $c->estCommence())
            ->sortBy(fn ($c) => $ordre[$c->niveau])
            ->values();

        BetCoupon::whereIn('id', $coupons->pluck('id'))->increment('vues');

        $historique = BetCoupon::publies()->verifies()
            ->orderByDesc('date_coupon')->limit(30)->get();

        return view('bet.index', [
            'coupons'    => $coupons,
            'historique' => $historique,
            'stats'      => BetCoupon::stats(),
        ]);
    }

    public function admin()
    {
        $brouillons = BetCoupon::where('statut', 'brouillon')
            ->whereDate('date_coupon', today())
            ->orderByDesc('date_coupon')->get()
            ->reject(fn ($c) => $c->estCommence())
            ->values();

        $publies = BetCoupon::publies()
            ->orderByDesc('date_coupon')->limit(15)->get();

        return view('bet.admin', compact('brouillons', 'publies'));
    }

    public function publier(Request $request, BetCoupon $coupon)
    {
        $request->validate(['lien_betclic' => 'required|url']);

        $coupon->update([
            'lien_betclic' => $request->lien_betclic,
            'statut'       => 'publie',
        ]);

        return back()->with('success', "Coupon {$coupon->badge()['label']} publié ✅");
    }

    public function apiStore(Request $request)
    {
        abort_unless(
            hash_equals(config('services.bet.token'), (string) $request->header('X-Bet-Token')),
            403
        );

        $data = $request->validate([
            'date_coupon'  => 'required|date',
            'niveau'       => 'required|in:sur,equilibre,jackpot,crazy,goals,pepite05',
            'selections'   => 'required|array|min:1',
            'cote_totale'  => 'required|numeric|min:1',
            'analyse'      => 'nullable|string',
        ]);

        $coupon = BetCoupon::whereDate('date_coupon', $data['date_coupon'])
            ->where('niveau', $data['niveau'])
            ->first();

        if ($coupon) {
            $coupon->update($data);
        } else {
            $coupon = BetCoupon::create($data + ['statut' => 'brouillon', 'resultat' => 'en_attente']);
        }

        return response()->json(['ok' => true, 'id' => $coupon->id]);
    }

    public function apiEnAttente(Request $request)
    {
        abort_unless(
            hash_equals(config('services.bet.token'), (string) $request->header('X-Bet-Token')),
            403
        );

        $coupons = BetCoupon::publies()
            ->where('resultat', 'en_attente')
            ->whereDate('date_coupon', '<=', today()->subDay())
            ->get(['id', 'niveau', 'date_coupon', 'selections']);

        return response()->json(['coupons' => $coupons]);
    }

    public function apiResultat(Request $request, BetCoupon $coupon)
    {
        abort_unless(
            hash_equals(config('services.bet.token'), (string) $request->header('X-Bet-Token')),
            403
        );

        $data = $request->validate([
            'resultat'          => 'required|in:gagne,perdu',
            'details_resultat'  => 'nullable|array',
        ]);

        $coupon->update($data);

        return response()->json(['ok' => true]);
    }
}
