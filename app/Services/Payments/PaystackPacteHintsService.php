<?php

namespace App\Services\Payments;

use App\Services\PacteService;
use App\Services\Payments\Games\StoryGameHintsService;

/**
 * Packs d'indices du Pacte avec les caïmans payés via Paystack (référence PCT-H-…).
 * Relecture chez Paystack, contrôles et idempotence : StoryGameHintsService.
 */
class PaystackPacteHintsService extends StoryGameHintsService
{
    public const PREFIX = 'PCT-H-';

    public function __construct(PacteService $pacte)
    {
        parent::__construct($pacte);
    }

    protected function name(): string
    {
        return 'Le Pacte';
    }

    protected function notificationType(): string
    {
        return 'payment_pacte_hints';
    }
}
