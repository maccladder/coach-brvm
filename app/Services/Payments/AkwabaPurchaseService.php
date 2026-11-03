<?php

namespace App\Services\Payments;

use App\Models\MarketplaceProduct;
use App\Models\Payment;
use App\Models\User;
use App\Services\Affiliate\AffiliateCommissionService;
use App\Services\AkwabaService;
use App\Services\PaystackService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Achats Akwaba, communs à la page Paystack hébergée (/jeux/akwaba/buy) et au checkout
 * Mobile Money personnalisé (AkwabaPayable) : montants fixés ici, mêmes références, mêmes
 * métadonnées, donc même validation (webhook, retour navigateur, rattrapage admin).
 *  - full   : achat marketplace du produit akwaba (référence MP-…)
 *  - hints5 : pack de 5 indices (référence AKW-H-…)
 */
class AkwabaPurchaseService
{
    public const PRODUCTS = ['full', 'hints5'];

    public const LABELS = [
        'full'   => 'Akwaba · Histoire complète',
        'hints5' => 'Akwaba · 5 indices',
    ];

    public function __construct(
        private AkwabaService $akwaba,
        private AffiliateCommissionService $affiliateService,
        private PaystackService $paystack,
    ) {}

    /**
     * Montant (XOF) et code apporteur, toujours calculés côté serveur.
     *
     * @return array{amount: int, affiliate_code: ?string}
     */
    public function price(string $which, Request $request, MarketplaceProduct $product): array
    {
        if ($which === 'hints5') {
            return ['amount' => AkwabaService::HINTS_PACK_PRICE, 'affiliate_code' => null];
        }

        // Même règle d'affiliation que les autres jeux (produit non éligible : prix plein)
        $checkout = $this->affiliateService->resolveForCheckout($request, 'marketplace', $product->id, (int) $product->price);

        return ['amount' => (int) $checkout['amount'], 'affiliate_code' => $checkout['code']];
    }

    /** Enregistre l'achat en attente et renvoie la référence Paystack. */
    public function createPending(string $which, User $user, MarketplaceProduct $product, int $amount, ?string $affiliateCode, string $channel): string
    {
        if ($which === 'hints5') {
            return PaystackAkwabaHintsService::newReference($user);
        }

        // Achat marketplace classique (référence MP-) : webhook, réconciliation, e-mails,
        // affiliation et attributions admin fonctionnent comme pour les autres jeux.
        $reference = 'MP-' . now()->format('YmdHis') . '-' . Str::upper(Str::random(6));

        DB::table('marketplace_purchases')->updateOrInsert(
            ['user_id' => $user->id, 'product_id' => $product->id],
            [
                'status'       => 'pending',
                'amount'       => $amount,
                'provider'     => 'paystack',
                'provider_ref' => $reference,
                'paid_at'      => null,
                'updated_at'   => now(),
                'created_at'   => now(),
            ]
        );

        Payment::create([
            'transaction_id'        => $reference,
            'user_id'               => $user->id,
            'amount_paid'           => $amount,
            'amount_virtual'        => 0,
            'purpose'               => 'marketplace',
            'status'                => 'pending',
            'affiliate_code'        => $affiliateCode,
            'affiliate_base_amount' => $affiliateCode ? (int) $product->price : null,
            'meta'                  => [
                'product_id'    => $product->id,
                'product_slug'  => $product->slug,
                'product_title' => $product->title,
                'provider'      => 'paystack',
                'source'        => 'akwaba',
                'channel'       => $channel,
            ],
        ]);

        return $reference;
    }

    /** Métadonnées Paystack : relues par la validation (joueur, jeu, produit). */
    public function metadata(string $which, User $user, MarketplaceProduct $product): array
    {
        return ($which === 'full' ? ['purpose' => 'marketplace', 'product_id' => $product->id] : []) + [
            'user_id' => $user->id,
            'game'    => AkwabaService::SLUG,
            'product' => $which,
        ];
    }

    /**
     * Page Paystack hébergée (Mobile Money seulement). Renvoie l'URL, ou null si Paystack
     * refuse l'initialisation (l'achat en attente passe alors en échec).
     */
    public function startHosted(string $which, User $user, MarketplaceProduct $product, Request $request): ?string
    {
        ['amount' => $amount, 'affiliate_code' => $code] = $this->price($which, $request, $product);
        $reference = $this->createPending($which, $user, $product, $amount, $code, 'paystack_hosted');

        $url = $this->paystack->initialize([
            'email'        => $user->email,
            'amount'       => $amount, // XOF, PaystackService multiplie par 100
            'currency'     => 'XOF',
            'reference'    => $reference,
            'callback_url' => route('akwaba.payment.return'),
            'channels'     => ['mobile_money'],
            'metadata'     => $this->metadata($which, $user, $product),
        ]);

        if (!$url) {
            $this->markFailed($which, $reference);
            Log::error('Akwaba buy: initialisation Paystack échouée', ['user_id' => $user->id, 'reference' => $reference]);
            return null;
        }

        Log::info('Akwaba buy: paiement initialisé', ['user_id' => $user->id, 'product' => $which, 'reference' => $reference]);

        return $url;
    }

    private function markFailed(string $which, string $reference): void
    {
        if ($which !== 'full') {
            return;
        }

        DB::table('marketplace_purchases')->where('provider_ref', $reference)
            ->update(['status' => 'failed', 'updated_at' => now()]);
        Payment::where('transaction_id', $reference)->update(['status' => 'failed']);
    }
}
