<?php

namespace App\Services;

use App\Services\Games\StoryGameService;

/**
 * Le Pacte avec les caïmans de Yamoussoukro (resources/games/le-pacte/index.html + img/).
 * - Prologue et chapitres 1-2 gratuits ; 3 à 7 et les 5 fins débloqués par l'achat du produit
 *   marketplace « le-pacte » ou une attribution admin (admin_grants).
 * - Indices : 1 gratuit à vie par compte, puis packs de 5 (game_credits, kind « hints »).
 * - Progression : game_progress (product_id du produit le-pacte), data = {save, fins, ...}.
 * Déblocage, indices et crédits : StoryGameService, commun aux jeux à histoire.
 */
class PacteService extends StoryGameService
{
    public const SLUG = 'le-pacte';

    protected function emptyProgress(): array
    {
        return ['save' => null, 'fins' => []];
    }
}
