<?php

namespace App\Services\Payments;

use App\Services\AkwabaService;
use App\Services\Payments\Games\StoryGameHintsService;

/**
 * Packs d'indices d'Akwaba payés via Paystack (référence AKW-H-…).
 * Relecture chez Paystack, contrôles et idempotence : StoryGameHintsService.
 */
class PaystackAkwabaHintsService extends StoryGameHintsService
{
    public const PREFIX = 'AKW-H-';

    public function __construct(AkwabaService $akwaba)
    {
        parent::__construct($akwaba);
    }

    protected function name(): string
    {
        return 'Akwaba';
    }

    protected function notificationType(): string
    {
        return 'payment_akwaba_hints';
    }
}
