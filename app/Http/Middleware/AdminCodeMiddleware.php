<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class AdminCodeMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        if (!session('is_admin')) {
            return redirect()->route('admin.login.form')
                ->with('error', 'Authentification requise.');
        }

        return $next($request);
    }
}
