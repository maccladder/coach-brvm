<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Blade;
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

        // ✅ Modules masquables : @feature('boc_analysis') … @endfeature (config/features.php)
        Blade::if('feature', fn (string $name) => (bool) config('features.' . $name));

        // ✅ LettreCI — rate limit : 20 générations / heure par user
        RateLimiter::for('letter-generation', function (Request $request) {
            return Limit::perHour(20)->by($request->user()?->id ?: $request->ip());
        });

        // ✅ Akwaba — compteur anonyme de l'entonnoir (13 types possibles, chacun au plus une fois
        //    par onglet) : par session, avec un plafond par IP large, car beaucoup de joueurs
        //    mobiles partagent une IP (CGNAT)
        RateLimiter::for('akwaba-events', function (Request $request) {
            return [
                Limit::perMinute(30)->by('session:' . $request->session()->getId()),
                Limit::perMinute(300)->by('ip:' . $request->ip()),
            ];
        });

        // ✅ Akwaba — état du jeu (chargement, retour de paiement interrogé toutes les 2 s) :
        //    par compte ou par session, plafond par IP large pour les IP partagées (CGNAT)
        // ✅ Le Pacte — entonnoir anonyme : même règle qu'Akwaba, compteurs séparés
        RateLimiter::for('pacte-events', function (Request $request) {
            return [
                Limit::perMinute(30)->by('session:' . $request->session()->getId()),
                Limit::perMinute(300)->by('ip:' . $request->ip()),
            ];
        });

        // ✅ Le Pacte — même règle que l'état d'Akwaba, compteurs séparés
        RateLimiter::for('pacte-state', function (Request $request) {
            return [
                Limit::perMinute(60)->by($request->user()
                    ? 'user:' . $request->user()->id
                    : 'session:' . $request->session()->getId()),
                Limit::perMinute(600)->by('ip:' . $request->ip()),
            ];
        });

        RateLimiter::for('akwaba-state', function (Request $request) {
            return [
                Limit::perMinute(60)->by($request->user()
                    ? 'user:' . $request->user()->id
                    : 'session:' . $request->session()->getId()),
                Limit::perMinute(600)->by('ip:' . $request->ip()),
            ];
        });

        // ✅ Inscription — rate limit anti-bot : 5 tentatives / minute par IP
        RateLimiter::for('register', function (Request $request) {
            return Limit::perMinute(5)->by($request->ip());
        });
    }
}
