<?php

namespace App\Services\Games;

use App\Services\AkwabaService;
use App\Services\PacteService;

/**
 * Jeux à histoire servis depuis resources/games, avec leur page hôte publique dédiée.
 * Leur produit marketplace ne sert qu'à enregistrer l'achat : pas de téléchargement, pas de
 * game_html, pas d'achat depuis la fiche (le paiement se fait dans le jeu).
 */
final class StoryGames
{
    /** slug du produit => nom de la route de la page du jeu */
    private const PLAY_ROUTES = [
        AkwabaService::SLUG => 'akwaba.show',
        PacteService::SLUG  => 'pacte.show',
    ];

    public static function is(?string $slug): bool
    {
        return isset(self::PLAY_ROUTES[(string) $slug]);
    }

    /** URL de la page du jeu, ou null si ce n'est pas un jeu à histoire. */
    public static function playUrl(?string $slug): ?string
    {
        return self::is($slug) ? route(self::PLAY_ROUTES[$slug]) : null;
    }
}
