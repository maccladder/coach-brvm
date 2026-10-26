<?php

namespace App\Services;

use App\Models\CaurisAchat;
use App\Models\GameSave;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class CaurisService
{
    /**
     * Crédite les cauris dans la game_save du joueur.
     * Idempotent : sans effet si déjà crédité ou paiement non accepté.
     */
    public function crediterCauris(string $reference): void
    {
        DB::transaction(function () use ($reference) {
            $achat = CaurisAchat::where('reference', $reference)
                ->lockForUpdate()
                ->first();

            if (!$achat) {
                Log::warning('CaurisService::crediterCauris: achat non trouvé', [
                    'reference' => $reference,
                ]);
                return;
            }

            if ($achat->credited_at !== null) {
                Log::info('CaurisService::crediterCauris: déjà crédité, skip', [
                    'reference' => $reference,
                    'achat_id'  => $achat->id,
                ]);
                return;
            }

            if ($achat->status !== 'ACCEPTED') {
                Log::warning('CaurisService::crediterCauris: statut non accepté', [
                    'reference' => $reference,
                    'status'    => $achat->status,
                ]);
                return;
            }

            $save = GameSave::where('user_id', $achat->user_id)
                ->lockForUpdate()
                ->first();

            if (!$save) {
                $save = GameSave::create(['user_id' => $achat->user_id]);
                $save = GameSave::where('user_id', $achat->user_id)
                    ->lockForUpdate()
                    ->first();
            }

            $save->solde += $achat->cauris;
            $save->save();

            $achat->credited_at = now();
            $achat->save();

            Log::info('CaurisService::crediterCauris: crédité', [
                'reference' => $reference,
                'user_id'   => $achat->user_id,
                'cauris'    => $achat->cauris,
                'solde'     => $save->solde,
            ]);
        });
    }

    /**
     * Traitement d'un charge.success Paystack (webhook ou réconciliation) :
     * vérifie le montant, passe l'achat en ACCEPTED puis crédite les cauris.
     * Idempotent : sans effet si l'achat est déjà crédité.
     */
    public function validerPaiementPaystack(array $data): void
    {
        $reference  = (string) ($data['reference'] ?? '');
        $amountPaid = (int) round(((int) ($data['amount'] ?? 0)) / 100);

        DB::transaction(function () use ($reference, $amountPaid, $data) {
            $achat = CaurisAchat::where('reference', $reference)
                ->lockForUpdate()
                ->first();

            if (!$achat) {
                Log::warning('Paystack webhook CAURIS: achat non trouvé', [
                    'reference' => $reference,
                ]);
                return;
            }

            if ($achat->credited_at !== null) {
                Log::info('Paystack webhook CAURIS: déjà crédité, skip', [
                    'reference' => $reference,
                ]);
                return;
            }

            // Anti-fraude : le montant payé doit correspondre exactement au pack
            if ($achat->prix !== $amountPaid) {
                Log::error('Paystack webhook CAURIS: montant incorrect', [
                    'reference'  => $reference,
                    'db_prix'    => $achat->prix,
                    'paid'       => $amountPaid,
                ]);
                $achat->update(['status' => 'REFUSED']);
                return;
            }

            $achat->update([
                'status' => 'ACCEPTED',
                'meta'   => ['paystack' => [
                    'event'   => 'charge.success',
                    'channel' => $data['channel'] ?? null,
                    'paid_at' => $data['paid_at'] ?? null,
                ]],
            ]);

            Log::info('Paystack webhook CAURIS: achat accepté', [
                'reference' => $reference,
                'user_id'   => $achat->user_id,
                'cauris'    => $achat->cauris,
            ]);
        });

        // Crédit des cauris (idempotent — sa propre transaction avec lockForUpdate)
        $this->crediterCauris($reference);
    }

    /**
     * Crée un achat en attente (avant paiement Paystack).
     */
    public function creerAchatEnAttente(int $userId, string $pack): CaurisAchat
    {
        $conf = config('cauris.packs')[$pack] ?? null;
        if (!$conf) {
            throw new \InvalidArgumentException("Pack cauris inconnu : {$pack}");
        }

        return CaurisAchat::create([
            'user_id'   => $userId,
            'reference' => 'CAURIS-' . Str::uuid(),
            'pack'      => $pack,
            'prix'      => $conf['prix'],
            'cauris'    => $conf['cauris'],
            'status'    => 'PENDING',
        ]);
    }
}
