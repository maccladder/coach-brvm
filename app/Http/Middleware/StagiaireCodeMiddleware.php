<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class StagiaireCodeMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        if (!session('is_stagiaire') && !session('is_admin')) {
            return redirect()->route('stagiaire.login.form')
                ->with('error', 'Authentification requise.');
        }

        return $next($request);
    }
}
