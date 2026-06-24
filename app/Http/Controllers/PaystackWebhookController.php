<?php

namespace App\Http\Controllers;

use App\Models\CaurisAchat;
use App\Models\LettreCIAccess;
use App\Services\CaurisService;
use App\Services\Payments\PaystackPackService;
use App\Services\Payments\PaystackWalletTopupService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PaystackWebhookController extends Controller
{
    public function handle(Request $request, PaystackWalletTopupService $walletTopup, PaystackPackService $packService)
    {
        $signature = $request->header('x-paystack-signature');
        $payload   = $request->getContent();
        $secret    = env('PAYSTACK_SECRET_KEY');

        if (!$signature || !$secret) {
            Log::warning('Paystack webhook missing signature or secret');
            return response()->json(['status' => 'invalid'], 400);
        }

        $computed = hash_hmac('sha512', $payload, $secret);

        if (!hash_equals($computed, $signature)) {
            Log::error('Paystack webhook invalid signature');
            return response()->json(['status' => 'unauthorized'], 401);
        }

        $event = (string) $request->input('event');
        $data  = (array) $request->input('data', []);

        Log::info('Paystack webhook received', [
            'event' => $event,
            'reference' => $data['reference'] ?? null,
        ]);

        if ($event === 'charge.success') {
            $reference = (string) ($data['reference'] ?? '');

            // ✅ Cauris (achat de monnaie de jeu)
            if (str_starts_with($reference, 'CAURIS-')) {
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
                app(CaurisService::class)->crediterCauris($reference);

                return response()->json(['status' => 'ok'], 200);
            }

            // ✅ Pack BRVM
            if (str_starts_with($reference, 'PACK-BRVM-')) {
                $packService->handleChargeSuccess($data);
                return response()->json(['status' => 'ok'], 200);
            }

            // ✅ LettreCI
            if (str_starts_with($reference, 'LETTRECI_')) {
                $access = LettreCIAccess::where('payment_ref', $reference)->first();
                if ($access && $access->status === 'pending') {
                    $access->update([
                        'status'  => 'active',
                        'paid_at' => now(),
                    ]);
                    Log::info('LettreCI access activated via webhook', ['reference' => $reference]);
                }
                return response()->json(['status' => 'ok'], 200);
            }

            // ✅ Délégation wallet
            $walletTopup->handleChargeSuccess($data);
        }

        return response()->json(['status' => 'ok'], 200);
    }
}
