<?php

namespace App\Checkout\Providers;

use App\Checkout\Payable;
use App\Checkout\PayableProvider;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

abstract class BasePayable implements PayableProvider
{
    public function requiresAuth(): bool
    {
        return true;
    }

    public function metadata(Payable $payable, ?User $user): array
    {
        return array_merge([
            'purpose' => $payable->type,
            'user_id' => $user?->id,
            'channel' => 'mobile_money_checkout',
        ], $payable->context['metadata'] ?? []);
    }

    /** Paiements de la table payments : validé = credited_at renseigné. */
    protected function paymentIsPaid(string $reference): bool
    {
        return Payment::where('transaction_id', $reference)->whereNotNull('credited_at')->exists();
    }

    protected function image(?string $path): ?string
    {
        return $path ? asset('storage/' . $path) : null;
    }

    /**
     * Ancien flux exposé en JSON (fetch depuis un jeu) : on l'appelle avec classic=1
     * et on redirige vers l'URL Paystack qu'il renvoie.
     */
    protected function redirectFromLegacyJson(Request $request, callable $call, string $fallbackUrl): Response
    {
        $request->merge(['classic' => 1]);

        $response = $call();

        if ($response instanceof JsonResponse) {
            $data = $response->getData(true);
            $url  = $data['authorization_url'] ?? $data['url'] ?? null;

            if ($url) {
                return redirect()->away($url);
            }

            return redirect($fallbackUrl)->with('error', $data['error'] ?? 'Paiement indisponible pour le moment.');
        }

        return $response;
    }
}
