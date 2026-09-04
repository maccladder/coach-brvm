<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use App\View\Composers\TickerComposer;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        Paginator::useBootstrapFive();

        // ✅ Ticker BRVM — vrais cours injectés dans le layout
        View::composer('layouts.app', TickerComposer::class);

        // ✅ LettreCI — rate limit : 20 générations / heure par user
        RateLimiter::for('letter-generation', function (Request $request) {
            return Limit::perHour(20)->by($request->user()?->id ?: $request->ip());
        });

        // ✅ Inscription — rate limit anti-bot : 5 tentatives / minute par IP
        RateLimiter::for('register', function (Request $request) {
            return Limit::perMinute(5)->by($request->ip());
        });
    }
}
