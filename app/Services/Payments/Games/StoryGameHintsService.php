<?php

namespace App\Services\Payments\Games;

use App\Models\AdminNotification;
use App\Models\User;
use App\Services\Games\StoryGameService;
use App\Services\Payments\PaystackMobileMoneyCheckout;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Packs d'indices d'un jeu à histoire payés via Paystack (référence {PREFIX}…).
 * Utilisé par le webhook et le retour navigateur : la transaction est toujours relue chez
 * Paystack, puis contrôlée (statut, montant, devise, métadonnées posées côté serveur).
 * Idempotent : payment_reference est unique dans game_credit_ledger.
 *
 * Chaque jeu définit la constante PREFIX, son nom et le type de sa notification admin.
 */
abstract class StoryGameHintsService
{
    public const GRANTED   = 'granted';
    public const ALREADY   = 'already';
    public const NOT_FOUND = 'not_found';
    public const MISMATCH  = 'mismatch';

    public function __construct(protected StoryGameService $game) {}

    /** Nom du jeu dans les journaux et la notification admin (ex. « Akwaba »). */
    abstract protected function name(): string;

    /** Type de la notification admin (ex. « payment_akwaba_hints »). */
    abstract protected function notificationType(): string;

    public static function newReference(User $user): string
    {
        return static::PREFIX . $user->id . '-' . round(microtime(true) * 1000) . '-' . strtoupper(Str::random(4));
    }

    /**
     * @param array{admin_user_id?: ?int, admin_label?: ?string} $admin renseigné pour un rattrapage manuel
     */
    public function handle(string $reference, array $admin = []): string
    {
        $name = $this->name();

        if (!str_starts_with($reference, static::PREFIX)) {
            return self::NOT_FOUND;
        }

        // Paystack (ou le simulateur en local, jamais en production) : jamais les données reçues
        $data = app(PaystackMobileMoneyCheckout::class)->verify($reference);

        if (!$data) {
            Log::warning("{$name} hints: transaction introuvable chez Paystack", ['reference' => $reference]);
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
            || $amountSubunits !== StoryGameService::HINTS_PACK_PRICE * 100
            || $currency !== 'XOF'
            || ($metadata['game'] ?? null) !== $this->game->slug()
            || ($metadata['product'] ?? null) !== 'hints5') {
            Log::error("{$name} hints: transaction refusée", [
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
            Log::warning("{$name} hints: joueur introuvable", ['reference' => $reference, 'metadata' => $metadata]);
            return self::NOT_FOUND;
        }

        try {
            $balance = $this->game->addHints(
                $user,
                StoryGameService::HINTS_PACK_SIZE,
                'purchase',
                $reference,
                $amountPaid,
                $admin ? 'Rattrapage manuel depuis l\'admin' : null,
                $admin
            );
        } catch (UniqueConstraintViolationException) {
            return self::ALREADY;
        }

        Log::info("{$name} hints: pack crédité", [
            'reference' => $reference,
            'user_id'   => $user->id,
            'balance'   => $balance,
        ]);

        try {
            AdminNotification::create([
                'type'    => $this->notificationType(),
                'title'   => $name . ' : pack de 5 indices',
                'message' => $user->name . ' — ' . StoryGameService::HINTS_PACK_PRICE . ' FCFA',
                'url'     => route('admin.dashboard'),
                'read_at' => null,
            ]);
        } catch (\Throwable $e) {
            Log::error("{$name} hints: notification admin", ['reference' => $reference, 'error' => $e->getMessage()]);
        }

        return self::GRANTED;
    }
}
