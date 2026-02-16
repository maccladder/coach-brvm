<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureVendorMode
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        // doit être vendeur
        if (!$user || !$user->is_vendor) {
            abort(403);
        }

        // si tu veux bloquer certains vendeurs plus tard
        if ($user->vendor_status === 'blocked') {
            abort(403, 'Votre compte vendeur est bloqué.');
        }

        // doit être en mode vendeur
        if (session('view_mode', 'user') !== 'vendor') {
            return redirect()->route('dashboard')
                ->with('warning', 'Passez en vue vendeur pour accéder à cet espace.');
        }

        return $next($request);
    }
}
