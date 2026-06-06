<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureAffiliateActive
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        if (!$user || !$user->is_affiliate) {
            return redirect()->route('affiliate.landing')
                ->with('warning', 'Tu dois être apporteur pour accéder à cet espace.');
        }

        $affiliate = $user->affiliate;

        if (!$affiliate || $affiliate->status === 'pending') {
            return redirect()->route('affiliate.landing')
                ->with('warning', '⏳ Ton compte apporteur est en attente de validation par l\'équipe.');
        }

        if ($affiliate->status === 'suspended') {
            return redirect()->route('affiliate.landing')
                ->with('warning', '⛔ Ton compte apporteur est suspendu.');
        }

        if ($affiliate->status !== 'active') {
            return redirect()->route('affiliate.landing')
                ->with('warning', 'Statut apporteur invalide. Contacte le support.');
        }

        return $next($request);
    }
}
