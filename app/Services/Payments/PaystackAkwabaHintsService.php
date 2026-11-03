<?php

namespace App\Services\Payments;

use App\Models\AdminNotification;
use App\Models\User;
use App\Services\AkwabaService;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Packs d'indices d'Akwaba payés via Paystack (référence AKW-H-…).
 * Utilisé par le webhook et le retour navigateur : la transaction est toujours relue chez
 * Paystack, puis contrôlée (statut, montant, devise, métadonnées posées côté serveur).
 * Idempotent : payment_reference est unique dans game_credit_ledger.
 */
class PaystackAkwabaHintsService
{
    public const PREFIX = 'AKW-H-';

    public const GRANTED   = 'granted';
    public const ALREADY   = 'already';
    public const NOT_FOUND = 'not_found';
    public const MISMATCH  = 'mismatch';

    public function __construct(private AkwabaService $akwaba) {}

    public static function newReference(User $user): string
    {
        return self::PREFIX . $user->id . '-' . round(microtime(true) * 1000) . '-' . strtoupper(Str::random(4));
    }

    /**
     * @param array{admin_user_id?: ?int, admin_label?: ?string} $admin renseigné pour un rattrapage manuel
     */
    public function handle(string $reference, array $admin = []): string
    {
        if (!str_starts_with($reference, self::PREFIX)) {
            return self::NOT_FOUND;
        }

        // Paystack (ou le simulateur en local, jamais en production) : jamais les données reçues
        $data = app(PaystackMobileMoneyCheckout::class)->verify($reference);

        if (!$data) {
            Log::warning('Akwaba hints: transaction introuvable chez Paystack', ['reference' => $reference]);
            return self::NOT_FOUND;
        }

        // Montant comparé en sous-unités exactes (un arrondi laisserait passer 499,51 F)
        $amountSubunits = (int) ($data['amount'] ?? 0);
        $amountPaid     = intdiv($amountSubunits, 100);
        $currency   = strtoupper((string) ($data['currency'] ?? ''));
        $status     = (string) ($data['status'] ?? '');
        $metadata   = $data['metadata'] ?? [];
        if (is_string($metadata)) {
            $metadata = json_decode($metadata, true) ?: [];
        }

        if (($data['reference'] ?? $reference) !== $reference
            || $status !== 'success'
            || $amountSubunits !== AkwabaService::HINTS_PACK_PRICE * 100
            || $currency !== 'XOF'
            || ($metadata['game'] ?? null) !== AkwabaService::SLUG
            || ($metadata['product'] ?? null) !== 'hints5') {
            Log::error('Akwaba hints: transaction refusée', [
                'reference' => $reference,
                'status'    => $status,
                'paid'      => $amountPaid,
                'currency'  => $currency,
                'metadata'  => $metadata,
            ]);
            return self::MISMATCH;
        }

        $user = User::find((int) ($metadata['user_id'] ?? 0));

        if (!$user) {
            Log::warning('Akwaba hints: joueur introuvable', ['reference' => $reference, 'metadata' => $metadata]);
            return self::NOT_FOUND;
        }

        try {
            $balance = $this->akwaba->addHints(
                $user,
                AkwabaService::HINTS_PACK_SIZE,
                'purchase',
                $reference,
                $amountPaid,
                $admin ? 'Rattrapage manuel depuis l\'admin' : null,
                $admin
            );
        } catch (UniqueConstraintViolationException) {
            return self::ALREADY;
        }

        Log::info('Akwaba hints: pack crédité', [
            'reference' => $reference,
            'user_id'   => $user->id,
            'balance'   => $balance,
        ]);

        try {
            AdminNotification::create([
                'type'    => 'payment_akwaba_hints',
                'title'   => 'Akwaba : pack de 5 indices',
                'message' => $user->name . ' — ' . AkwabaService::HINTS_PACK_PRICE . ' FCFA',
                'url'     => route('admin.dashboard'),
                'read_at' => null,
            ]);
        } catch (\Throwable $e) {
            Log::error('Akwaba hints: notification admin', ['reference' => $reference, 'error' => $e->getMessage()]);
        }

        return self::GRANTED;
    }
}
