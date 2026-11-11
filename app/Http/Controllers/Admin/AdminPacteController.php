<?php

namespace App\Http\Controllers\Admin;

use App\Services\Games\StoryGameService;
use App\Services\PacteService;
use App\Services\Payments\Games\StoryGameHintsService;
use App\Services\Payments\PaystackPacteHintsService;

/**
 * Admin du Pacte avec les caïmans (/admin/le-pacte, middleware admin.code) : même page
 * qu'Akwaba (StoryGameAdminController), avec les points de sauvegarde et les fins du Pacte.
 */
class AdminPacteController extends StoryGameAdminController
{
    public function __construct(private PacteService $pacte) {}

    protected function game(): StoryGameService
    {
        return $this->pacte;
    }

    protected function hintsService(): StoryGameHintsService
    {
        return app(PaystackPacteHintsService::class);
    }

    protected function config(): array
    {
        return [
            'name'         => 'Le Pacte',
            'title'        => '🐊 Le Pacte avec les caïmans de Yamoussoukro',
            'page_title'   => '🐊 Le Pacte — Admin',
            'route'        => 'admin.pacte',
            'play_route'   => 'pacte.show',
            'hints_prefix' => PaystackPacteHintsService::PREFIX,
            // Points de sauvegarde du jeu (finGratuite = écran du cadenas, compté à part)
            'funnel_saves' => [
                'gare'        => 'Prologue',
                'villa'       => 'Chapitre 1',
                'funerailles' => 'Chapitre 2',
                'chap3'       => 'Chapitre 3',
                'chap4'       => 'Chapitre 4',
                'chap5'       => 'Chapitre 5',
                'chap6'       => 'Chapitre 6',
                'chap7'       => 'Chapitre 7',
            ],
            'short_labels' => [
                'gare' => 'Prologue', 'villa' => 'Ch. 1', 'funerailles' => 'Ch. 2', 'finGratuite' => 'Cadenas',
                'chap3' => 'Ch. 3', 'chap4' => 'Ch. 4', 'chap5' => 'Ch. 5', 'chap6' => 'Ch. 6', 'chap7' => 'Ch. 7',
            ],
            'lock_before'    => 'chap3',
            'lock_threshold' => 'finGratuite',
            'lock_help'    => "Cadenas = a atteint l'écran « Fin de la partie gratuite » en étant connecté, a tenté d'acheter la suite, ou a l'accès.",
            'fins'         => [
                'pacte'     => 'Rompre le pacte',
                'justice'   => 'La justice',
                'argent'    => "Garder l'argent",
                'gardien'   => 'Le nouveau gardien',
                'sacrifice' => 'Le sacrifice',
            ],
            'loaded_types' => ['page_loaded'],
            'details'      => [
                ['continue',         '« Continuer »',               ['continue'],         false],
                ['free_hint',        'indice gratuit',              ['free_hint'],        false],
                ['shop_open',        "boutique d'indices ouverte",  ['shop_open'],        false],
                ['buy_click_full',   'clic « histoire complète »',  ['buy_click_full'],   false],
                ['buy_click_hints5', 'clic « 5 indices »',          ['buy_click_hints5'], false],
                ['lang_fr',          'passage en français',         ['lang_fr'],          false],
                ['lang_en',          'passage en anglais',          ['lang_en'],          false],
            ],
            'browser_stages' => [
                ['page',     'Page ouverte',       ['page_open']],
                ['loaded',   'Jeu chargé',         ['page_loaded']],
                ['start',    'Partie',             ['new_game', 'continue']],
                ['ch2',      'Chapitre 2',         ['ch2_reached']],
                ['lock',     'Cadenas',            ['paywall_open', 'lock_reached']],
                ['buy',      'Clic acheter',       ['buy_click_full', 'buy_click_hints5']],
                ['external', 'Ouvert dans Chrome', ['open_external']],
            ],
            'browser_unknown_note' => false,
            'sessions_note'        => false,
            'errors'               => false,
        ];
    }
}
