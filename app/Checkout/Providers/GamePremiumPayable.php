<?php

namespace App\Checkout\Providers;

use App\Checkout\CheckoutRedirect;
use App\Checkout\Payable;
use App\Checkout\StartsClassicFlow;
use App\Http\Controllers\GameScoreController;
use App\Models\GamePremiumUnlock;
use App\Models\MarketplaceProduct;
use App\Models\User;
use App\Services\Payments\PaystackGamePremiumService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class GamePremiumPayable extends BasePayable implements StartsClassicFlow
{
    public function __construct(private PaystackGamePremiumService $premiumService) {}

    public function resolve(Request $request, ?User $user): Payable
    {
        $product = MarketplaceProduct::find((int) $request->input('product'));
        $feature = (string) $request->input('feature', 'premium_chars');

        if (!$product || $product->type !== 'game') {
            throw CheckoutRedirect::to(route('my.products'), 'error', 'Jeu introuvable.');
        }

        // Mêmes règles que GameScoreController::initPremiumPayment
        $hasAccess = $user->purchasedProducts()
                ->where('marketplace_products.id', $product->id)
                ->wherePivot('status', 'paid')
                ->exists()
            || $product->isGrantedTo($user);

        if (!$hasAccess) {
            throw CheckoutRedirect::to(route('marketplace.show', $product->slug), 'error', 'Accès refusé.');
        }

        $price = GameScoreController::featurePrice($product->slug, $feature);

        if ($price === null) {
            throw CheckoutRedirect::to(route('my.products.play', $product), 'error', 'Fonctionnalité inconnue.');
        }

        $unlocked = GamePremiumUnlock::where('user_id', $user->id)
            ->where('product_id', $product->id)
            ->where('feature', $feature)
            ->exists();

        if ($unlocked) {
            throw CheckoutRedirect::to(route('my.products.play', $product), 'success', 'Déjà débloqué ✅');
        }

        $label = ucfirst(str_replace('_', ' ', $feature));

        return new Payable(
            type: 'game_premium',
            itemKey: 'game:' . $product->id . ':' . $feature,
            title: $product->title . ' — ' . $label,
            amount: $price,
            email: $user->email,
            successUrl: route('my.products.play', $product),
            successLabel: 'Jouer maintenant',
            image: $product->vignette_url ?? $this->image($product->cover_image_path),
            badges: ['Accès à vie', 'Téléphone et ordi'],
            params: ['product' => $product->id, 'feature' => $feature],
            icon: '⭐',
            context: [
                'product'  => $product,
                'feature'  => $feature,
                'metadata' => ['product_id' => $product->id, 'feature' => $feature],
            ],
        );
    }

    public function createPending(Payable $payable, ?User $user): string
    {
        // Pas d'enregistrement préalable dans l'ancien flux : le déblocage est créé à la validation
        return 'GAME' . $payable->context['product']->id . '_' . strtoupper($payable->context['feature']) . '_' . round(microtime(true) * 1000);
    }

    public function validate(array $data): void
    {
        $this->premiumService->handleGamePremium($data);
    }

    public function isPaid(string $reference): bool
    {
        return GamePremiumUnlock::where('paystack_ref', $reference)->exists();
    }

    public function classicForm(Payable $payable): array
    {
        return ['action' => route('checkout.mm.classic', 'game_premium'), 'fields' => $payable->params];
    }

    public function startClassic(Request $request, ?User $user): Response
    {
        $product = MarketplaceProduct::findOrFail((int) $request->input('product'));

        return $this->redirectFromLegacyJson(
            $request,
            fn () => app()->call([app(GameScoreController::class), 'initPremiumPayment'], ['product' => $product]),
            route('my.products.play', $product)
        );
    }
}
