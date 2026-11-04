<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/**
 * « feature:nom » : 404 si config('features.nom') est à false (module masqué, pas supprimé).
 * « feature:nom,admin » : idem, mais une session admin (code admin ou stagiaire) passe, pour
 * consulter les données existantes ; à réserver aux routes de lecture, jamais à la création.
 *
 * 404 plutôt qu'une redirection vers l'accueil : pas de « soft 404 » pour Google, et l'adresse
 * revient telle quelle si le module est réactivé.
 */
class EnsureFeatureEnabled
{
    public function handle(Request $request, Closure $next, string $feature, ?string $mode = null)
    {
        if (config('features.' . $feature)) {
            return $next($request);
        }

        if ($mode === 'admin' && (session('is_admin') || session('is_stagiaire'))) {
            return $next($request);
        }

        abort(404);
    }
}
