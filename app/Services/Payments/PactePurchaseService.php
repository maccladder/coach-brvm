<?php

namespace App\Services\Payments;

use App\Models\User;
use App\Services\Affiliate\AffiliateCommissionService;
use App\Services\PacteService;
use App\Services\Payments\Games\StoryGamePurchaseService;
use App\Services\PaystackService;

/**
 * Achats du Pacte avec les caïmans (page Paystack hébergée /jeux/le-pacte/buy et checkout
 * Mobile Money PactePayable). Montants, références et métadonnées : StoryGamePurchaseService.
 *  - full   : achat marketplace du produit le-pacte (référence MP-…)
 *  - hints5 : pack de 5 indices (référence PCT-H-…)
 */
class PactePurchaseService extends StoryGamePurchaseService
{
    public const LABELS = [
        'full'   => 'Le Pacte · Histoire complète',
        'hints5' => 'Le Pacte · 5 indices',
    ];

    public function __construct(PacteService $pacte, AffiliateCommissionService $affiliateService, PaystackService $paystack)
    {
        parent::__construct($pacte, $affiliateService, $paystack);
    }

    protected function name(): string
    {
        return 'Le Pacte';
    }

    protected function returnRoute(): string
    {
        return 'pacte.payment.return';
    }

    protected function hintsReference(User $user): string
    {
        return PaystackPacteHintsService::newReference($user);
    }
}
