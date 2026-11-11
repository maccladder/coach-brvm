<?php

namespace App\Checkout\Providers;

use App\Checkout\CheckoutRedirect;
use App\Checkout\Payable;
use App\Checkout\StartsClassicFlow;
use App\Models\GameCreditLedger;
use App\Models\User;
use App\Services\Games\StoryGameService;
use App\Services\Payments\Games\StoryGameHintsService;
use App\Services\Payments\Games\StoryGamePurchaseService;
use App\Services\Payments\PaystackMarketplaceService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Un jeu à histoire dans le checkout Mobile Money : histoire complète (achat marketplace MP-,
 * validé par PaystackMarketplaceService comme le webhook) ou pack de 5 indices (validé par le
 * service d'indices du jeu, qui relit toujours la transaction). Montants, références et
 * métadonnées viennent du service d'achats du jeu, comme pour la page Paystack hébergée.
 *
 * Chaque jeu définit son type de checkout, sa route et ses libellés.
 */
abstract class StoryGamePayable extends BasePayable implements StartsClassicFlow
{
    public function __construct(
        protected StoryGameService $game,
        protected StoryGamePurchaseService $purchases,
        protected PaystackMarketplaceService $marketplaceService,
        protected StoryGameHintsService $hintsService,
    ) {}

    /** Type du checkout (config/checkout.php), ex. « akwaba ». */
    abstract protected function type(): string;

    /** Nom de la route de la page du jeu, ex. « akwaba.show ». */
    abstract protected function showRoute(): string;

    /** Pastilles affichées pour l'histoire complète. */
    abstract protected function fullBadges(): array;

    abstract protected function fullIcon(): string;

    public function resolve(Request $request, ?User $user): Payable
    {
        $which   = (string) $request->input('product');
        $product = $this->game->product();
        $show    = $this->showRoute();

        if (!$product || !in_array($which, StoryGamePurchaseService::PRODUCTS, true)) {
            throw CheckoutRedirect::to(route($show));
        }

        if ($which === 'full' && $this->game->isUnlocked($user, $product)) {
            throw CheckoutRedirect::to(route($show), 'success', "L'histoire complète est déjà débloquée.");
        }

        ['amount' => $amount, 'affiliate_code' => $code] = $this->purchases->price($which, $request, $product);

        $full = $which === 'full';

        return new Payable(
            type: $this->type(),
            itemKey: $this->type() . ':' . $which,
            title: $this->purchases::LABELS[$which],
            amount: $amount,
            email: $user->email,
            successUrl: route($show, ['paid' => 1]),
            successLabel: 'Retour au jeu',
            image: $product->cover_url,
            badges: $full ? $this->fullBadges() : ['5 indices', 'Utilisables quand tu veux'],
            params: ['product' => $which],
            offerActivation: true,
            icon: $full ? $this->fullIcon() : '💡',
            context: [
                'which'          => $which,
                'product'        => $product,
                'affiliate_code' => $code,
                'metadata'       => $this->purchases->metadata($which, $user, $product),
            ],
            successTitle: $full ? "C'est payé : la suite de l'histoire est débloquée !" : "C'est payé : +5 indices !",
            cancelUrl: route($show, ['paiement' => 'annule', 'produit' => $which]),
            failUrl: route($show, ['paiement' => 'echec', 'produit' => $which]),
            failLabel: 'Retour au jeu',
            autoReturnSeconds: 2,
        );
    }

    public function metadata(Payable $payable, ?User $user): array
    {
        // Les clés du jeu (user_id, game, product) priment : ce sont elles que la validation relit
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

        if (str_starts_with($reference, $this->hintsService::PREFIX)) {
            // Relit la transaction (Paystack ou simulateur) : les données reçues ne servent pas
            $this->hintsService->handle($reference);
            return;
        }

        $this->marketplaceService->handleChargeSuccess($data);
    }

    public function isPaid(string $reference): bool
    {
        return str_starts_with($reference, $this->hintsService::PREFIX)
            ? GameCreditLedger::where('payment_reference', $reference)->exists()
            : $this->paymentIsPaid($reference);
    }

    public function classicForm(Payable $payable): array
    {
        return ['action' => route('checkout.mm.classic', $this->type()), 'fields' => $payable->params];
    }

    /** Page Paystack hébergée : Wave (tant que son Charge API n'est pas activé) et secours */
    public function startClassic(Request $request, ?User $user): Response
    {
        $payable = $this->resolve($request, $user);
        $url     = $this->purchases->startHosted($payable->context['which'], $user, $payable->context['product'], $request);

        if (!$url) {
            return redirect()->route($this->showRoute(), ['paiement' => 'echec', 'produit' => $payable->context['which']]);
        }

        return redirect()->away($url);
    }
}
