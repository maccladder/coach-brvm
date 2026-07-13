<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class N8nApiKeyMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $provided = (string) $request->header('X-API-KEY');
        $expected = (string) config('services.n8n.api_key');

        if ($expected === '' || !hash_equals($expected, $provided)) {
            return response()->json(['status' => 'unauthorized'], 401);
        }

        return $next($request);
    }
}
