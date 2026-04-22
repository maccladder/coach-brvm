<?php

namespace App\Http\Controllers;

use App\Models\LettreCIAccess;
use App\Services\Payments\PaystackWalletTopupService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PaystackWebhookController extends Controller
{
    public function handle(Request $request, PaystackWalletTopupService $walletTopup)
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
