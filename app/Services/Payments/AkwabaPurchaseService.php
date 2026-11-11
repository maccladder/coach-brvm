<?php

namespace App\Services\Payments;

use App\Models\User;
use App\Services\Affiliate\AffiliateCommissionService;
use App\Services\AkwabaService;
use App\Services\Payments\Games\StoryGamePurchaseService;
use App\Services\PaystackService;

/**
 * Achats Akwaba (page Paystack hébergée /jeux/akwaba/buy et checkout Mobile Money AkwabaPayable).
 * Montants, références et métadonnées : StoryGamePurchaseService.
 *  - full   : achat marketplace du produit akwaba (référence MP-…)
 *  - hints5 : pack de 5 indices (référence AKW-H-…)
 */
class AkwabaPurchaseService extends StoryGamePurchaseService
{
    public const LABELS = [
        'full'   => 'Akwaba · Histoire complète',
        'hints5' => 'Akwaba · 5 indices',
    ];

    public function __construct(AkwabaService $akwaba, AffiliateCommissionService $affiliateService, PaystackService $paystack)
    {
        parent::__construct($akwaba, $affiliateService, $paystack);
    }

    protected function name(): string
    {
        return 'Akwaba';
    }

    protected function returnRoute(): string
    {
        return 'akwaba.payment.return';
    }

    protected function hintsReference(User $user): string
    {
        return PaystackAkwabaHintsService::newReference($user);
    }
}
