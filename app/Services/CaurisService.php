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
