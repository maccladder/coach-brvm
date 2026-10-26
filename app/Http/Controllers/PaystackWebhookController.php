<?php

namespace App\Http\Controllers;

use App\Jobs\ForwardAdomCiWebhook;
use App\Models\LettreCIAccess;
use App\Models\Payment;
use App\Services\CaurisService;
use App\Services\Payments\PaystackCourseService;
use App\Services\Payments\PaystackGamePremiumService;
use App\Services\Payments\PaystackMarketplaceService;
use App\Services\Payments\PaystackPackService;
use App\Services\Payments\PaystackWalletTopupService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PaystackWebhookController extends Controller
{
    public function handle(Request $request, PaystackWalletTopupService $walletTopup, PaystackPackService $packService)
    {
        $signature = $request->header('x-paystack-signature');
        $payload   = $request->getContent();
        $secret    = config('services.paystack.secret') ?: env('PAYSTACK_SECRET_KEY');

        if (!$signature || !$secret) {
            Log::warning('Paystack webhook missing signature or secret');
            return response()->json(['status' => 'invalid'], 400);
        }

        $computed = hash_hmac('sha512', $payload, $secret);

        if (!hash_equals($computed, $signature)) {
            Log::error('Paystack webhook invalid signature');
            return response()->json(['status' => 'unauthorized'], 401);
        }

        $event     = (string) $request->input('event');
        $data      = (array) $request->input('data', []);
        $reference = (string) ($data['reference'] ?? '');

        Log::info('Paystack webhook received', [
            'event' => $event,
            'reference' => $reference ?: null,
        ]);

        // Signature valide : on répond toujours 200, même si le traitement échoue
        // (la commande payments:reconcile-paystack rattrape les cas manqués).
        try {
            // ✅ ADOM CI (autre application, même compte Paystack) : tous les événements
            if (str_starts_with($reference, 'ADOM-CI-')) {
                $this->forwardToAdomCi($payload, (string) $signature, $reference, $event);
            } elseif ($event === 'charge.success') {
                $this->handleChargeSuccess($reference, $data, $walletTopup, $packService);
            }
        } catch (\Throwable $e) {
            Log::error('Paystack webhook: erreur de traitement', [
                'event'     => $event,
                'reference' => $reference,
                'error'     => $e->getMessage(),
            ]);
        }

        return response()->json(['status' => 'ok'], 200);
    }

    private function handleChargeSuccess(
        string $reference,
        array $data,
        PaystackWalletTopupService $walletTopup,
        PaystackPackService $packService
    ): void {
        // ✅ Cauris (achat de monnaie de jeu)
        if (str_starts_with($reference, 'CAURIS-')) {
            app(CaurisService::class)->validerPaiementPaystack($data);
            return;
        }

        // ✅ Pack BRVM
        if (str_starts_with($reference, 'PACK-BRVM-')) {
            $packService->handleChargeSuccess($data);
            return;
        }

        // ✅ Marketplace (jeux, logiciels…)
        if (str_starts_with($reference, 'MP-')) {
            $outcome = app(PaystackMarketplaceService::class)->handleChargeSuccess($data);
            Log::info('Paystack webhook MP', ['reference' => $reference, 'outcome' => $outcome]);
            return;
        }

        // ✅ Formations
        if (str_starts_with($reference, 'COURSE-')) {
            $outcome = app(PaystackCourseService::class)->handleChargeSuccess($data);
            Log::info('Paystack webhook COURSE', ['reference' => $reference, 'outcome' => $outcome]);
            return;
        }

        // ✅ Ancien déblocage premium Abidjan Run
        if (str_starts_with($reference, 'ABRUN_PREM_')) {
            $outcome = app(PaystackGamePremiumService::class)->handleLegacyAbidjanRun($data);
            Log::info('Paystack webhook ABRUN_PREM', ['reference' => $reference, 'outcome' => $outcome]);
            return;
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
            return;
        }

        // ✅ Dons : confirmés par le callback /faire-un-don/callback, rien à faire ici
        if (str_starts_with($reference, 'DON-')) {
            Log::info('Paystack webhook DON: confirmé par le callback, non traité ici', $this->summary($data));
            return;
        }

        // ✅ Délégation wallet (WALLET_… et tout paiement existant de la table payments)
        if (str_starts_with($reference, 'WALLET_')
            || ($reference !== '' && Payment::where('transaction_id', $reference)->exists())) {
            $walletTopup->handleChargeSuccess($data);
            return;
        }

        // Références inconnues (UUID, autres applications…)
        Log::warning('Paystack webhook: référence non gérée', $this->summary($data));
    }

    private function forwardToAdomCi(string $payload, string $signature, string $reference, string $event): void
    {
        $url = (string) config('services.adom_ci.webhook_url');

        if ($url === '') {
            Log::info('Paystack webhook ADOM-CI: aucune URL de transfert (ADOM_CI_WEBHOOK_URL), ignoré', [
                'reference' => $reference,
                'event'     => $event,
            ]);
            return;
        }

        ForwardAdomCiWebhook::dispatch($url, $payload, $signature, $reference);

        Log::info('Paystack webhook ADOM-CI: transfert programmé', [
            'reference' => $reference,
            'event'     => $event,
        ]);
    }

    private function summary(array $data): array
    {
        return [
            'reference' => $data['reference'] ?? null,
            'email'     => data_get($data, 'customer.email'),
            'amount'    => isset($data['amount']) ? (int) round(((int) $data['amount']) / 100) : null,
            'currency'  => $data['currency'] ?? null,
            'paid_at'   => $data['paid_at'] ?? ($data['created_at'] ?? null),
            'channel'   => $data['channel'] ?? null,
        ];
    }
}
