<?php

namespace App\Http\Controllers;

use App\Models\Donation;
use App\Services\PaystackService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class DonationController extends Controller
{
    // ────────────────────────────────────────────
    // Page du don
    // ────────────────────────────────────────────

    public function show()
    {
        return view('donations.index');
    }

    // ────────────────────────────────────────────
    // Initiation du paiement Paystack
    // ────────────────────────────────────────────

    public function initiate(Request $request, PaystackService $paystack)
    {
        $validated = $request->validate([
            'donor_name'  => ['required', 'string', 'max:120'],
            'donor_email' => ['required', 'email', 'max:190'],
            'amount'      => ['required', 'integer', 'min:500', 'max:10000000'],
        ], [
            'donor_name.required'  => 'Votre nom est requis.',
            'donor_email.required' => 'Votre adresse email est requise.',
            'donor_email.email'    => 'L\'adresse email saisie est invalide.',
            'amount.required'      => 'Veuillez choisir ou saisir un montant.',
            'amount.min'           => 'Le montant minimum est de 500 FCFA.',
        ]);

        $reference = 'DON-' . strtoupper(Str::random(12));

        $donation = Donation::create([
            'reference'   => $reference,
            'donor_name'  => $validated['donor_name'],
            'donor_email' => $validated['donor_email'],
            'amount'      => (int) $validated['amount'],
            'currency'    => 'XOF',
            'status'      => 'PENDING',
            'meta'        => ['provider' => 'paystack'],
        ]);

        $authUrl = $paystack->initialize([
            'email'        => $donation->donor_email,
            'amount'       => $donation->amount,
            'reference'    => $reference,
            'callback_url' => route('donation.callback', [], true),
            'metadata'     => [
                'purpose'    => 'donation',
                'donor_name' => $donation->donor_name,
            ],
        ]);

        if (!$authUrl) {
            $donation->update(['status' => 'FAILED']);
            return back()->with('error', "Impossible d'initialiser le paiement. Veuillez réessayer.");
        }

        return redirect()->away($authUrl);
    }

    // ────────────────────────────────────────────
    // Callback Paystack (retour après paiement)
    // ────────────────────────────────────────────

    public function callback(Request $request, PaystackService $paystack)
    {
        $reference = (string) ($request->query('reference') ?: $request->query('trxref'));

        Log::info('[DONATION][CALLBACK] hit', [
            'reference' => $reference,
            'url'       => $request->fullUrl(),
        ]);

        if (!$reference) {
            return redirect()->route('donation.show')->with('error', 'Référence de paiement manquante.');
        }

        $donation = Donation::where('reference', $reference)->first();

        if (!$donation) {
            Log::error('[DONATION][CALLBACK] donation not found', ['reference' => $reference]);
            return redirect()->route('donation.show')->with('error', 'Don introuvable.');
        }

        // Idempotence : déjà confirmé
        if ($donation->status === 'ACCEPTED') {
            return redirect()->route('donation.merci');
        }

        $data = $paystack->verify($reference);

        if (!$data || ($data['status'] ?? null) !== 'success') {
            $donation->update(['status' => 'FAILED']);
            return redirect()->route('donation.show')
                ->with('error', 'Le paiement n\'a pas été validé ou a été annulé.');
        }

        $paid = (int) (($data['amount'] ?? 0) / 100);

        if ($paid <= 0 || $paid !== (int) $donation->amount) {
            Log::error('[DONATION][CALLBACK] amount mismatch', [
                'reference' => $reference,
                'expected'  => $donation->amount,
                'received'  => $paid,
            ]);
            $donation->update(['status' => 'FAILED']);
            return redirect()->route('donation.show')->with('error', 'Montant reçu invalide.');
        }

        $donation->update([
            'status'       => 'ACCEPTED',
            'confirmed_at' => now(),
            'meta'         => array_merge((array) ($donation->meta ?? []), [
                'paystack' => [
                    'reference' => $reference,
                    'channel'   => $data['channel'] ?? null,
                    'paid_at'   => $data['paid_at'] ?? null,
                ],
            ]),
        ]);

        return redirect()->route('donation.merci', ['ref' => $reference]);
    }

    // ────────────────────────────────────────────
    // Page de remerciement
    // ────────────────────────────────────────────

    public function merci(Request $request)
    {
        $donation = null;
        if ($ref = $request->query('ref')) {
            $donation = Donation::where('reference', $ref)
                ->where('status', 'ACCEPTED')
                ->first();
        }

        return view('donations.merci', compact('donation'));
    }
}
