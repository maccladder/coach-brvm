<?php

namespace App\Checkout\Providers;

use App\Services\PacteService;
use App\Services\Payments\PactePurchaseService;
use App\Services\Payments\PaystackMarketplaceService;
use App\Services\Payments\PaystackPacteHintsService;

/**
 * Le Pacte avec les caïmans dans le checkout Mobile Money : histoire complète (MP-) ou pack de
 * 5 indices (PCT-H-). Logique commune aux jeux à histoire : StoryGamePayable.
 */
class PactePayable extends StoryGamePayable
{
    public function __construct(
        PacteService $pacte,
        PactePurchaseService $purchases,
        PaystackMarketplaceService $marketplaceService,
        PaystackPacteHintsService $hintsService,
    ) {
        parent::__construct($pacte, $purchases, $marketplaceService, $hintsService);
    }

    protected function type(): string
    {
        return 'pacte';
    }

    protected function showRoute(): string
    {
        return 'pacte.show';
    }

    protected function fullBadges(): array
    {
        return ['Chapitres 3 à 7 + 5 fins', 'Accès à vie'];
    }

    protected function fullIcon(): string
    {
        return '🐊';
    }
}
