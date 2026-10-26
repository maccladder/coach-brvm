<?php

namespace App\Checkout;

use App\Models\User;
use Illuminate\Http\Request;

/**
 * Un type de paiement branché sur le checkout Mobile Money.
 * Chaque implémentation réutilise la validation existante du type (webhook / callback).
 */
interface PayableProvider
{
    public function requiresAuth(): bool;

    /**
     * Construit le payable depuis les paramètres de la requête.
     * Lève CheckoutRedirect quand il n'y a rien à payer (déjà acheté, gratuit, introuvable…).
     */
    public function resolve(Request $request, ?User $user): Payable;

    /**
     * Crée les enregistrements "en attente", comme l'ancien flux, et retourne la
     * référence Paystack (le préfixe détermine le routage du webhook).
     */
    public function createPending(Payable $payable, ?User $user): string;

    /** Validation idempotente (montant + devise XOF) à partir du bloc "data" Paystack. */
    public function validate(array $data): void;

    /** Le paiement de cette référence est-il validé en base ? */
    public function isPaid(string $reference): bool;

    /**
     * Formulaire POST vers l'ancien flux Paystack ("Autre moyen de paiement").
     *
     * @return array{action: string, fields: array}
     */
    public function classicForm(Payable $payable): array;

    /** Métadonnées envoyées à Paystack avec le charge. */
    public function metadata(Payable $payable, ?User $user): array;
}
