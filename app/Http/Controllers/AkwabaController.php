<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Services\Affiliate\AffiliateCommissionService;
use App\Services\AkwabaService;
use App\Services\Payments\PaystackAkwabaHintsService;
use App\Services\Payments\PaystackMarketplaceService;
use App\Services\PaystackService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Akwaba : API appelée par la page hôte (pont postMessage), qui relaie les messages du jeu.
 * Montants et produits fixés côté serveur ; le client ne choisit que « full » ou « hints5 ».
 */
class AkwabaController extends Controller
{
    public function __construct(private AkwabaService $akwaba) {}

    /**
     * GET /jeux/akwaba
     * Page hôte publique : tout le monde joue au prologue et aux chapitres 1-2 sans compte.
     */
    public function show(Request $request)
    {
        abort_unless(is_file($this->gamePath()), 404);

        return view('akwaba-play', [
            'product'  => $this->akwaba->product(),
            'version'  => $this->fileVersion(),
            'paid'     => $request->boolean('paid'),
            'whatsapp' => (string) config('checkout.whatsapp'),
        ]);
    }

    /**
     * GET /jeux/akwaba/connexion
     * Connexion demandée au cadenas : retour sur le jeu après login (formulaire ou Google).
     */
    public function login(Request $request)
    {
        if ($request->user()) {
            return redirect()->route('akwaba.show');
        }

        redirect()->setIntendedUrl(route('akwaba.show'));

        return redirect()->route('login');
    }

    /**
     * GET /jeux/akwaba/fichier?v={hash}
     * Le jeu tel quel (un seul fichier autonome), sans passer par le pipeline d'upload.
     * Cache long seulement sous l'URL versionnée par le hash du contenu : une nouvelle version
     * du jeu change l'URL. Sans version (ou avec une ancienne), le navigateur revalide toujours.
     */
    public function file(Request $request)
    {
        $path = $this->gamePath();
        abort_unless(is_file($path), 404);

        $cache = $request->query('v') === $this->fileVersion()
            ? 'public, max-age=31536000, immutable'
            : 'no-cache';

        return response()->file($path, [
            'Content-Type'           => 'text/html; charset=UTF-8',
            'Cache-Control'          => $cache,
            'X-Frame-Options'        => 'SAMEORIGIN',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    private function gamePath(): string
    {
        return resource_path('games/akwaba.html');
    }

    /** Hash du contenu, recalculé seulement quand le fichier change (date ou taille). */
    private function fileVersion(): string
    {
        $path = $this->gamePath();

        return Cache::rememberForever(
            'akwaba.file.version.' . filemtime($path) . '.' . filesize($path),
            fn () => substr(hash_file('sha256', $path), 0, 16)
        );
    }

    /**
     * GET /jeux/akwaba/state
     */
    public function state(Request $request)
    {
        return response()->json($this->akwaba->state($request->user()))
            ->header('Cache-Control', 'no-store');
    }

    /**
     * POST /jeux/akwaba/progress  {save: 'P'|'1'..'7'|null, fins: ['oubli','gardien']}
     */
    public function progress(Request $request)
    {
        $product = $this->akwaba->product();
        abort_unless($product, 404);

        $data = $request->validate([
            'save'   => ['present', 'nullable', 'string', 'in:' . implode(',', AkwabaService::SAVES)],
            'fins'   => ['sometimes', 'array', 'max:' . count(AkwabaService::FINS)],
            'fins.*' => ['string', 'distinct', 'in:' . implode(',', AkwabaService::FINS)],
        ]);

        $save = $data['save'] ?? null;
        $fins = $data['fins'] ?? [];
        $user = $request->user();

        // Sans déblocage, seuls le prologue et les chapitres 1-2 sont atteignables
        $beyondFree = ($save !== null && !in_array($save, AkwabaService::FREE_SAVES, true)) || $fins !== [];
        if ($beyondFree && !$this->akwaba->isUnlocked($user, $product)) {
            return response()->json(['error' => 'Histoire non débloquée'], 403);
        }

        return response()->json(['progress' => $this->akwaba->saveProgress($user, $product, $save, $fins)]);
    }

    /**
     * POST /jeux/akwaba/hint/use  {id}
     */
    public function useHint(Request $request)
    {
        $data = $request->validate([
            'id' => ['required', 'string', 'max:64'],
        ]);

        return response()->json(['id' => $data['id']] + $this->akwaba->useHint($request->user()));
    }

    /**
     * POST /jeux/akwaba/buy  {product: 'full'|'hints5'}
     * Renvoie l'URL de la page Paystack hébergée (Mobile Money uniquement).
     */
    public function buy(Request $request, PaystackService $paystack, AffiliateCommissionService $affiliateService)
    {
        $data = $request->validate([
            'product' => ['required', 'string', 'in:full,hints5'],
        ]);

        $product = $this->akwaba->product();
        abort_unless($product, 404);

        $user = $request->user();

        if ($data['product'] === 'full') {
            if ($this->akwaba->isUnlocked($user, $product)) {
                return response()->json(['error' => 'Histoire déjà débloquée', 'already' => true], 409);
            }

            if ((int) $product->price <= 0) {
                Log::error('Akwaba buy: prix du produit invalide', ['product_id' => $product->id]);
                return response()->json(['error' => 'Produit indisponible'], 503);
            }

            // Achat marketplace classique (référence MP-) : webhook, réconciliation, e-mails,
            // affiliation et attributions admin fonctionnent comme pour les autres jeux.
            $checkout  = $affiliateService->resolveForCheckout($request, 'marketplace', $product->id, (int) $product->price);
            $amount    = (int) $checkout['amount'];
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
                'affiliate_code'        => $checkout['code'],
                'affiliate_base_amount' => $checkout['code'] ? (int) $product->price : null,
                'meta'                  => [
                    'product_id'    => $product->id,
                    'product_slug'  => $product->slug,
                    'product_title' => $product->title,
                    'provider'      => 'paystack',
                    'source'        => 'akwaba',
                ],
            ]);

            $metadata = ['purpose' => 'marketplace', 'product_id' => $product->id];
        } else {
            $amount    = AkwabaService::HINTS_PACK_PRICE;
            $reference = PaystackAkwabaHintsService::newReference($user);
            $metadata  = [];
        }

        $url = $paystack->initialize([
            'email'        => $user->email,
            'amount'       => $amount, // XOF, PaystackService multiplie par 100
            'currency'     => 'XOF',
            'reference'    => $reference,
            'callback_url' => route('akwaba.payment.return'),
            'channels'     => ['mobile_money'],
            'metadata'     => $metadata + [
                'user_id' => $user->id,
                'game'    => AkwabaService::SLUG,
                'product' => $data['product'],
            ],
        ]);

        if (!$url) {
            if ($data['product'] === 'full') {
                DB::table('marketplace_purchases')->where('provider_ref', $reference)
                    ->update(['status' => 'failed', 'updated_at' => now()]);
                Payment::where('transaction_id', $reference)->update(['status' => 'failed']);
            }

            Log::error('Akwaba buy: initialisation Paystack échouée', ['user_id' => $user->id, 'reference' => $reference]);
            return response()->json(['error' => "Impossible d'initialiser le paiement."], 502);
        }

        Log::info('Akwaba buy: paiement initialisé', [
            'user_id'   => $user->id,
            'product'   => $data['product'],
            'reference' => $reference,
        ]);

        return response()->json(['url' => $url]);
    }

    /**
     * GET /jeux/akwaba/paiement/retour?reference=…
     * Retour Paystack (filet si le webhook tarde ; il reste la source de vérité). Idempotent :
     * le premier des deux qui arrive valide, l'autre ne fait rien.
     */
    public function paymentReturn(Request $request, PaystackService $paystack, PaystackMarketplaceService $marketplace, PaystackAkwabaHintsService $hints)
    {
        $reference = (string) ($request->query('reference') ?: $request->query('trxref', ''));

        if (str_starts_with($reference, PaystackAkwabaHintsService::PREFIX)) {
            $outcome = $hints->handle($reference);
            Log::info('Akwaba retour hints', ['reference' => $reference, 'outcome' => $outcome]);
        } elseif (str_starts_with($reference, 'MP-')) {
            $isAkwaba = data_get(Payment::where('transaction_id', $reference)->first()?->meta, 'product_slug') === AkwabaService::SLUG;
            $data     = $isAkwaba ? $paystack->verify($reference) : null;

            if ($data && ($data['status'] ?? null) === 'success') {
                $outcome = $marketplace->handleChargeSuccess($data);
                Log::info('Akwaba retour full', ['reference' => $reference, 'outcome' => $outcome]);
            }
        }

        return redirect()->route('akwaba.show', ['paid' => 1]);
    }
}
