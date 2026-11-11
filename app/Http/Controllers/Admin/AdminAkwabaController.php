<?php

namespace App\Http\Controllers\Admin;

use App\Services\AkwabaService;
use App\Services\Games\StoryGameService;
use App\Services\Payments\Games\StoryGameHintsService;
use App\Services\Payments\PaystackAkwabaHintsService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Admin d'Akwaba (/admin/akwaba, middleware admin.code) : joueurs, entonnoir, ventes,
 * historique des indices, erreurs du jeu, et gestes manuels (déblocage, indices, rattrapage
 * d'un pack). Logique commune aux jeux à histoire : StoryGameAdminController.
 */
class AdminAkwabaController extends StoryGameAdminController
{
    public function __construct(private AkwabaService $akwaba) {}

    protected function game(): StoryGameService
    {
        return $this->akwaba;
    }

    protected function hintsService(): StoryGameHintsService
    {
        return app(PaystackAkwabaHintsService::class);
    }

    protected function config(): array
    {
        $saves = ['P' => 'Prologue'];
        foreach (range(1, 7) as $n) {
            $saves[(string) $n] = 'Chapitre ' . $n;
        }

        return [
            'name'         => 'Akwaba',
            'title'        => '🌙 Akwaba : le mystère de la lagune Ébrié',
            'page_title'   => '🌙 Akwaba — Admin',
            'route'        => 'admin.akwaba',
            'play_route'   => 'akwaba.show',
            'hints_prefix' => PaystackAkwabaHintsService::PREFIX,
            'funnel_saves' => $saves,
            'short_labels' => ['P' => 'Prologue'] + collect(range(1, 7))->mapWithKeys(fn ($n) => [(string) $n => 'Ch. ' . $n])->all(),
            'lock_before'    => '3',
            'lock_threshold' => '3',
            'lock_help'    => "Cadenas = a cliqué « Continuer l'aventure » en étant connecté, a l'accès, ou a dépassé le chapitre 2.",
            'fins'         => ['oubli' => "Fin de l'oubli", 'gardien' => 'Fin du gardien'],
            'loaded_types' => ['page_loaded', 'play_start'], // play_start : ancien nom
            'details'      => [
                ['continue',         '« Continuer »',                                ['continue'],         false],
                ['free_hint',        'indice gratuit',                               ['free_hint'],        false],
                ['shop_open',        "boutique d'indices ouverte",                   ['shop_open'],        false],
                ['buy_click_full',   'clic « histoire complète »',                   ['buy_click_full'],   false],
                ['buy_click_hints5', 'clic « 5 indices »',                           ['buy_click_hints5'], false],
                ['wait_rest',        'a dû attendre la fin du chargement des images', ['wait_rest'],       true],
            ],
            'browser_stages' => [
                ['page',     'Page ouverte',       ['page_open']],
                ['loaded',   'Jeu chargé',         ['page_loaded', 'play_start']],
                ['start',    'Partie',             ['new_game', 'continue']],
                ['ch2',      'Chapitre 2',         ['ch2_reached']],
                ['lock',     'Cadenas',            ['paywall_open', 'lock_reached']],
                ['buy',      'Clic acheter',       ['buy_click_full', 'buy_click_hints5']],
                ['wait',     'Attente images',     ['wait_rest']],
                ['external', 'Ouvert dans Chrome', ['open_external']], // clic du bandeau des navigateurs intégrés
            ],
            'browser_unknown_note' => true,  // « Inconnu » = visites d'avant le 04/11/2026
            'sessions_note'        => true,  // étapes détaillées mesurées depuis le 03/11/2026
            'errors'               => true,
        ];
    }

    /** 50 dernières erreurs JavaScript du jeu, regroupées par message (nombre, dernière date, navigateurs). */
    protected function recentErrors(): array
    {
        return DB::table('akwaba_errors')
            ->latest('id')
            ->limit(50)
            ->get()
            ->groupBy('msg')
            ->map(fn ($group, $msg) => [
                'msg'      => $msg,
                'count'    => $group->count(),
                'last_at'  => Carbon::parse($group->max('created_at')),
                'position' => $group->first()->line !== null ? $group->first()->line . ':' . ($group->first()->col ?? '?') : null,
                'browsers' => $group->countBy('browser')->sortDesc()->all(),
            ])
            ->sortByDesc('count')
            ->values()
            ->all();
    }
}
