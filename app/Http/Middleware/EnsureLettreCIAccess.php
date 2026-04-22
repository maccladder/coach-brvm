<?php

namespace App\Http\Middleware;

use App\Models\LettreCIAccess;
use Closure;
use Illuminate\Http\Request;

class EnsureLettreCIAccess
{
    public function handle(Request $request, Closure $next)
    {
        if (!$request->user() || !LettreCIAccess::hasActiveAccess($request->user())) {
            return redirect()->route('lettreci.landing')
                ->with('warning', 'Vous devez acheter l\'accès LettreCI pour continuer.');
        }

        return $next($request);
    }
}
