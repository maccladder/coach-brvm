<?php

namespace App\Checkout\Providers;

use App\Checkout\CheckoutRedirect;
use App\Checkout\Payable;
use App\Checkout\StartsClassicFlow;
use App\Http\Controllers\MarketplacePaymentController;
use App\Models\MarketplaceProduct;
use App\Models\Payment;
use App\Models\User;
use App\Services\Affiliate\AffiliateCommissionService;
use App\Services\Payments\PaystackMarketplaceService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class MarketplacePayable extends BasePayable implements StartsClassicFlow
{
    public function __construct(
        private AffiliateCommissionService $affiliateService,
        private PaystackMarketplaceService $marketplaceService,
    ) {}

    public function resolve(Request $request, ?User $user): Payable
    {
        $product = MarketplaceProduct::find((int) $request->input('product'));

        if (!$product) {
            throw CheckoutRedirect::to(route('marketplace.index'), 'error', 'Produit introuvable.');
        }

        // Les articles gratuits gardent leur flux "claim gratuit"
        if ((int) $product->price <= 0) {
            throw CheckoutRedirect::to(route('marketplace.show', $product->slug));
        }

        $alreadyPaid = $user->purchasedProducts()
            ->where('marketplace_products.id', $product->id)
            ->wherePivot('status', 'paid')
            ->exists();

        if ($alreadyPaid) {
            throw CheckoutRedirect::to(route('marketplace.show', $product->slug), 'success', '✅ Produit déjà acheté. Tu peux le télécharger / regarder.');
        }

        // Même résolution du code apporteur que l'ancien flux (champ du formulaire > cookie)
        $checkout = $this->affiliateService->resolveForCheckout($request, 'marketplace', $product->id, (int) $product->price);

        [$badges, $label, $url, $icon] = $this->presentation($product);

        return new Payable(
            type: 'marketplace',
            itemKey: 'product:' . $product->id,
            title: $product->title,
            amount: (int) $checkout['amount'],
            email: $user->email,
            successUrl: $url,
            successLabel: $label,
            image: $product->vignette_url ?? $this->image($product->cover_image_path),
            badges: $badges,
            params: array_filter([
                'product'        => $product->id,
                'affiliate_code' => $request->input('affiliate_code'),
            ]),
            offerActivation: true,
            icon: $icon,
            context: [
                'product'        => $product,
                'affiliate_code' => $checkout['code'] ?? null,
                'metadata'       => ['product_id' => $product->id],
            ],
        );
    }

    /** Badges et bouton de l'écran succès selon le type de produit. */
    private function presentation(MarketplaceProduct $product): array
    {
        return match ($product->type) {
            'game'     => [['Accès à vie', 'Téléphone et ordi'], 'Jouer maintenant', route('my.products.play', $product), '🎮'],
            'book'     => [['Accès à vie', 'PDF téléchargeable'], 'Télécharger le livre', route('my.products.download', $product), '📘'],
            'video'    => [['Accès à vie', 'Streaming'], 'Regarder maintenant', route('my.products.watch', $product), '🎬'],
            'software' => [['Licence', 'Téléchargement'], 'Télécharger / activer',
                PaystackMarketplaceService::isSoftwareActivationProduct($product)
                    ? route('marketplace.activation', $product->id)
                    : route('my.products.download', $product), '💻'],
            'course', 'formation' => [['Accès à vie', 'Vidéos et supports'], 'Commencer la formation', route('my.products'), '🎓'],
            default    => [['Accès à vie'], 'Voir mes produits', route('my.products'), '🛒'],
        };
    }

    public function createPending(Payable $payable, ?User $user): string
    {
        /** @var MarketplaceProduct $product */
        $product       = $payable->context['product'];
        $affiliateCode = $payable->context['affiliate_code'];
        $reference     = 'MP-' . now()->format('YmdHis') . '-' . Str::upper(Str::random(6));

        // Identique à MarketplacePaymentController::buyPaystack
        DB::table('marketplace_purchases')->updateOrInsert(
            ['user_id' => $user->id, 'product_id' => $product->id],
            [
                'status'       => 'pending',
                'amount'       => $payable->amount,
                'provider'     => 'paystack',
                'provider_ref' => $reference,
                'paid_at'      => null,
                'updated_at'   => now(),
                'created_at'   => now(),
            ]
        );

        Payment::create([
            'user_id'               => $user->id,
            'transaction_id'        => $reference,
            'amount_paid'           => $payable->amount,
            'amount_virtual'        => 0,
            'purpose'               => 'marketplace',
            'status'                => 'pending',
            'credited_at'           => null,
            'affiliate_code'        => $affiliateCode,
            'affiliate_base_amount' => $affiliateCode ? (int) $product->price : null,
            'meta'                  => [
                'product_id'    => $product->id,
                'product_slug'  => $product->slug,
                'product_title' => $product->title,
                'provider'      => 'paystack',
                'channel'       => 'mobile_money_checkout',
            ],
        ]);

        return $reference;
    }

    public function validate(array $data): void
    {
        $this->marketplaceService->handleChargeSuccess($data);
    }

    public function isPaid(string $reference): bool
    {
        return $this->paymentIsPaid($reference);
    }

    public function classicForm(Payable $payable): array
    {
        return [
            'action' => route('paystack.marketplace.buy', $payable->context['product']),
            'fields' => array_filter([
                'classic'        => 1,
                'affiliate_code' => $payable->params['affiliate_code'] ?? null,
            ]),
        ];
    }

    /** Ancien flux Paystack (page hébergée), ex. Wave tant que son Charge API n'est pas activé */
    public function startClassic(Request $request, ?User $user): Response
    {
        $product = MarketplaceProduct::findOrFail((int) $request->input('product'));
        $request->merge(['classic' => 1]);

        return app()->call([app(MarketplacePaymentController::class), 'buyPaystack'], ['product' => $product]);
    }
}
