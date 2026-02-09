<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PaystackWebhookController extends Controller
{
    public function handle(Request $request)
    {
        // 1️⃣ Vérification signature Paystack (HMAC SHA512)
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

        // 2️⃣ Payload valide
        $event = $request->input('event');
        $data  = $request->input('data');

        Log::info('Paystack webhook received', [
            'event' => $event,
            'reference' => $data['reference'] ?? null,
        ]);

        // 3️⃣ On ne traite QUE les paiements réussis
        if ($event === 'charge.success') {
            $reference = $data['reference'] ?? null;
            $amount    = ($data['amount'] ?? 0) / 100; // retour en FCFA
            $email     = $data['customer']['email'] ?? null;

            // ⚠️ ICI : branchement business
            // ex :
            // - crédit wallet
            // - valider commande marketplace
            // - marquer transaction payée

            Log::info('Paystack payment confirmed', [
                'reference' => $reference,
                'amount'    => $amount,
                'email'     => $email,
            ]);
        }

        return response()->json(['status' => 'ok'], 200);
    }
}
