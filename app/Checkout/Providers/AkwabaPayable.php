<?php

namespace App\Checkout\Providers;

use App\Services\AkwabaService;
use App\Services\Payments\AkwabaPurchaseService;
use App\Services\Payments\PaystackAkwabaHintsService;
use App\Services\Payments\PaystackMarketplaceService;

/**
 * Akwaba dans le checkout Mobile Money : histoire complète (MP-) ou pack de 5 indices (AKW-H-).
 * Logique commune aux jeux à histoire : StoryGamePayable.
 */
class AkwabaPayable extends StoryGamePayable
{
    public function __construct(
        AkwabaService $akwaba,
        AkwabaPurchaseService $purchases,
        PaystackMarketplaceService $marketplaceService,
        PaystackAkwabaHintsService $hintsService,
    ) {
        parent::__construct($akwaba, $purchases, $marketplaceService, $hintsService);
    }

    protected function type(): string
    {
        return 'akwaba';
    }

    protected function showRoute(): string
    {
        return 'akwaba.show';
    }

    protected function fullBadges(): array
    {
        return ['Chapitres 3 à 7 + 2 fins', 'Accès à vie'];
    }

    protected function fullIcon(): string
    {
        return '🌙';
    }
}
