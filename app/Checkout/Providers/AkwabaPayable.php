<?php

namespace App\Checkout\Providers;

use App\Checkout\CheckoutRedirect;
use App\Checkout\Payable;
use App\Checkout\StartsClassicFlow;
use App\Models\GameCreditLedger;
use App\Models\User;
use App\Services\AkwabaService;
use App\Services\Payments\AkwabaPurchaseService;
use App\Services\Payments\PaystackAkwabaHintsService;
use App\Services\Payments\PaystackMarketplaceService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Akwaba dans le checkout Mobile Money : histoire complète (achat marketplace MP-, validé par
 * PaystackMarketplaceService comme le webhook) ou pack de 5 indices (AKW-H-, validé par
 * PaystackAkwabaHintsService, qui relit toujours la transaction). Montants, références et
 * métadonnées viennent d'AkwabaPurchaseService, comme pour la page Paystack hébergée.
 */
class AkwabaPayable extends BasePayable implements StartsClassicFlow
{
    public function __construct(
        private AkwabaService $akwaba,
        private AkwabaPurchaseService $purchases,
        private PaystackMarketplaceService $marketplaceService,
        private PaystackAkwabaHintsService $hintsService,
    ) {}

    public function resolve(Request $request, ?User $user): Payable
    {
        $which   = (string) $request->input('product');
        $product = $this->akwaba->product();

        if (!$product || !in_array($which, AkwabaPurchaseService::PRODUCTS, true)) {
            throw CheckoutRedirect::to(route('akwaba.show'));
        }

        if ($which === 'full' && $this->akwaba->isUnlocked($user, $product)) {
            throw CheckoutRedirect::to(route('akwaba.show'), 'success', "L'histoire complète est déjà débloquée.");
        }

        ['amount' => $amount, 'affiliate_code' => $code] = $this->purchases->price($which, $request, $product);

        $full = $which === 'full';

        return new Payable(
            type: 'akwaba',
            itemKey: 'akwaba:' . $which,
            title: AkwabaPurchaseService::LABELS[$which],
            amount: $amount,
            email: $user->email,
            successUrl: route('akwaba.show', ['paid' => 1]),
            successLabel: 'Retour au jeu',
            image: $product->cover_url,
            badges: $full ? ['Chapitres 3 à 7 + 2 fins', 'Accès à vie'] : ['5 indices', 'Utilisables quand tu veux'],
            params: ['product' => $which],
            offerActivation: true,
            icon: $full ? '🌙' : '💡',
            context: [
                'which'          => $which,
                'product'        => $product,
                'affiliate_code' => $code,
                'metadata'       => $this->purchases->metadata($which, $user, $product),
            ],
            successTitle: $full ? "C'est payé : la suite de l'histoire est débloquée !" : "C'est payé : +5 indices !",
            cancelUrl: route('akwaba.show', ['paiement' => 'annule', 'produit' => $which]),
            failUrl: route('akwaba.show', ['paiement' => 'echec', 'produit' => $which]),
            failLabel: 'Retour au jeu',
            autoReturnSeconds: 2,
        );
    }

    public function metadata(Payable $payable, ?User $user): array
    {
        // Les clés Akwaba (user_id, game, product) priment : ce sont elles que la validation relit
        return array_merge(parent::metadata($payable, $user), $payable->context['metadata']);
    }

    public function createPending(Payable $payable, ?User $user): string
    {
        return $this->purchases->createPending(
            $payable->context['which'],
            $user,
            $payable->context['product'],
            $payable->amount,
            $payable->context['affiliate_code'],
            'mobile_money_checkout'
        );
    }

    public function validate(array $data): void
    {
        $reference = (string) ($data['reference'] ?? '');

        if (str_starts_with($reference, PaystackAkwabaHintsService::PREFIX)) {
            // Relit la transaction (Paystack ou simulateur) : les données reçues ne servent pas
            $this->hintsService->handle($reference);
            return;
        }

        $this->marketplaceService->handleChargeSuccess($data);
    }

    public function isPaid(string $reference): bool
    {
        return str_starts_with($reference, PaystackAkwabaHintsService::PREFIX)
            ? GameCreditLedger::where('payment_reference', $reference)->exists()
            : $this->paymentIsPaid($reference);
    }

    public function classicForm(Payable $payable): array
    {
        return ['action' => route('checkout.mm.classic', 'akwaba'), 'fields' => $payable->params];
    }

    /** Page Paystack hébergée : Wave (tant que son Charge API n'est pas activé) et secours */
    public function startClassic(Request $request, ?User $user): Response
    {
        $payable = $this->resolve($request, $user);
        $url     = $this->purchases->startHosted($payable->context['which'], $user, $payable->context['product'], $request);

        if (!$url) {
            return redirect()->route('akwaba.show', ['paiement' => 'echec', 'produit' => $payable->context['which']]);
        }

        return redirect()->away($url);
    }
}
