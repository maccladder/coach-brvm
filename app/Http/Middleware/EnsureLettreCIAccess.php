<?php

namespace App\Http\Middleware;

use App\Models\AdminGrant;
use App\Models\LettreCIAccess;
use Closure;
use Illuminate\Http\Request;

class EnsureLettreCIAccess
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        if (!$user) {
            return redirect()->route('lettreci.landing')
                ->with('warning', 'Vous devez acheter l\'accès LettreCI pour continuer.');
        }

        $hasAccess = LettreCIAccess::hasActiveAccess($user)
            || AdminGrant::hasLettreCIAccess($user);

        if (!$hasAccess) {
            return redirect()->route('lettreci.landing')
                ->with('warning', 'Vous devez acheter l\'accès LettreCI pour continuer.');
        }

        return $next($request);
    }
}
