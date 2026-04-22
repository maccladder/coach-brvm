<?php

namespace App\Http\Controllers\LettreCI;

use App\Http\Controllers\Controller;
use App\Models\LettreCIAccess;
use App\Services\PaystackService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class LettrePaymentController extends Controller
{
    public function __construct(private PaystackService $paystack) {}

    public function checkout(Request $request)
    {
        $user = $request->user();

        if (LettreCIAccess::hasActiveAccess($user)) {
            return redirect()->route('lettreci.dashboard')
                ->with('info', 'Vous avez déjà un accès LettreCI actif.');
        }

        $reference = 'LETTRECI_' . $user->id . '_' . time();
        $price     = config('services.lettreci.price');
        $currency  = config('services.lettreci.currency');

        // Crée l'accès en statut pending pour idempotence webhook
        LettreCIAccess::firstOrCreate(
            ['user_id' => $user->id, 'payment_ref' => $reference],
            ['amount' => $price, 'currency' => $currency, 'status' => 'pending']
        );

        $authUrl = $this->paystack->initialize([
            'email'        => $user->email,
            'amount'       => $price,
            'currency'     => $currency,
            'reference'    => $reference,
            'callback_url' => route('lettreci.payment.callback'),
            'metadata'     => ['user_id' => $user->id, 'module' => 'lettreci'],
        ]);

        if (!$authUrl) {
            Log::error('LettreCI Paystack init failed', ['user_id' => $user->id]);
            return back()->with('error', 'Impossible d\'initier le paiement. Veuillez réessayer.');
        }

        return redirect($authUrl);
    }

    public function callback(Request $request)
    {
        $reference = (string) $request->query('reference', '');

        if (!str_starts_with($reference, 'LETTRECI_')) {
            return redirect()->route('lettreci.landing')
                ->with('error', 'Référence de paiement invalide.');
        }

        $transaction = $this->paystack->verify($reference);

        if (!$transaction || ($transaction['status'] ?? '') !== 'success') {
            return redirect()->route('lettreci.landing')
                ->with('error', 'Le paiement n\'a pas pu être vérifié. Contactez le support si vous avez été débité.');
        }

        $access = LettreCIAccess::where('payment_ref', $reference)->first();

        if ($access && $access->status === 'pending') {
            $access->update([
                'status'  => 'active',
                'paid_at' => now(),
            ]);
        }

        return redirect()->route('lettreci.dashboard')
            ->with('success', '✅ Paiement confirmé ! Bienvenue sur LettreCI. Vous pouvez maintenant générer vos lettres.');
    }
}
