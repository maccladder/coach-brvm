<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Services\AkwabaService;
use App\Services\Payments\AkwabaPurchaseService;
use App\Services\Payments\PaystackAkwabaHintsService;
use App\Services\Payments\PaystackMarketplaceService;
use App\Services\Payments\PaystackMobileMoneyCheckout;
use App\Services\PaystackService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

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

        $nonce = base64_encode(random_bytes(16));

        return response()
            ->view('akwaba-play', [
                'product'  => $this->akwaba->product(),
                'version'  => $this->fileVersion(),
                'paid'     => $request->boolean('paid'),
                'whatsapp' => (string) config('checkout.whatsapp'),
                'nonce'    => $nonce,
                // Navigateur intégré (pub Facebook…) : bandeau « ouvre le jeu dans ton navigateur »
                'inApp'    => AkwabaService::inAppBrowser($request->userAgent()),
            ])
            // La page n'a qu'un script (le pont, avec nonce) et des styles inline ; tout le reste
            // vient du même domaine. Le jeu (iframe) a ses propres en-têtes, sur /fichier.
            ->header('Content-Security-Policy', implode('; ', [
                "default-src 'self'",
                "script-src 'nonce-{$nonce}'",
                "style-src 'self' 'unsafe-inline'",
                "img-src 'self' data:",
                "connect-src 'self'",
                "frame-src 'self'",
                "frame-ancestors 'self'",
                "base-uri 'none'",
                "form-action 'self'",
                "object-src 'none'",
            ]))
            ->header('X-Frame-Options', 'SAMEORIGIN')
            ->header('X-Content-Type-Options', 'nosniff')
            ->header('Referrer-Policy', 'same-origin');
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

        // Pas de CSP restrictive sur le jeu lui-même (scripts inline, data:, Web Audio : une règle
        // trop stricte le casserait sans qu'on puisse le relire) : seulement l'interdiction d'être
        // affiché dans un cadre d'un autre site.
        return response()->file($path, [
            'Content-Type'            => 'text/html; charset=UTF-8',
            'Cache-Control'           => $cache,
            'Content-Security-Policy' => "frame-ancestors 'self'",
            'X-Frame-Options'         => 'SAMEORIGIN',
            'X-Content-Type-Options'  => 'nosniff',
            'Referrer-Policy'         => 'same-origin',
        ]);
    }

    /**
     * GET /jeux/akwaba/images?v={hash}
     * Images du jeu au-delà du prologue (JSON), chargées par le jeu avec fetch à l'adresse
     * relative « images » + sa propre query string : même version et même cache que /fichier.
     * Compression : par nginx (gzip_types application/json), Laravel sert le fichier tel quel.
     */
    public function images(Request $request)
    {
        $path = $this->imagesPath();
        abort_unless(is_file($path), 404);

        $cache = $request->query('v') === $this->fileVersion()
            ? 'public, max-age=31536000, immutable'
            : 'no-cache';

        return response()->file($path, [
            'Content-Type'           => 'application/json; charset=UTF-8',
            'Cache-Control'          => $cache,
            'X-Content-Type-Options' => 'nosniff',
            // Simple donnée : rien à exécuter ni à afficher dans un cadre
            'Content-Security-Policy' => "default-src 'none'; frame-ancestors 'none'",
        ]);
    }

    private function gamePath(): string
    {
        return resource_path('games/akwaba.html');
    }

    private function imagesPath(): string
    {
        return resource_path('games/akwaba-images.json');
    }

    /**
     * Version commune au jeu et à ses images : hash des DEUX fichiers, recalculé seulement quand
     * l'un d'eux change (date ou taille). Une mise à jour de l'un ou l'autre change l'adresse.
     */
    private function fileVersion(): string
    {
        $paths = array_values(array_filter([$this->gamePath(), $this->imagesPath()], 'is_file'));
        $key   = implode('.', array_map(fn ($p) => filemtime($p) . '-' . filesize($p), $paths));

        return Cache::rememberForever(
            'akwaba.file.version.' . md5($key),
            fn () => substr(hash('sha256', implode('', array_map(fn ($p) => hash_file('sha256', $p), $paths))), 0, 16)
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
     * POST /jeux/akwaba/event  {type: un nom de AkwabaService::EVENTS}
     * Compteur anonyme de l'entonnoir, invités et connectés (une ligne par session et par type).
     */
    public function event(Request $request)
    {
        $data = $request->validate([
            'type' => ['required', 'string', 'in:' . implode(',', AkwabaService::EVENTS)],
        ]);

        $this->akwaba->recordEvent($data['type'], $request->session()->getId(), $request->userAgent());

        return response()->noContent();
    }

    /**
     * POST /jeux/akwaba/error  {msg, line?, col?}
     * Erreur JavaScript remontée par le jeu (message postMessage « error »), relayée par la page
     * hôte. Anonyme : ni IP, ni compte, ni session stockés avec l'erreur.
     */
    public function logError(Request $request)
    {
        $data = $request->validate([
            'msg'  => ['required', 'string', 'max:2000'],
            'line' => ['nullable', 'integer', 'min:0', 'max:10000000'],
            'col'  => ['nullable', 'integer', 'min:0', 'max:10000000'],
        ]);

        $this->akwaba->recordError(
            $data['msg'],
            isset($data['line']) ? (int) $data['line'] : null,
            isset($data['col']) ? (int) $data['col'] : null,
            $request->session()->getId(),
            $request->userAgent()
        );

        return response()->noContent();
    }

    /**
     * POST /jeux/akwaba/hint/free
     * L'indice gratuit (un seul par joueur, pour toujours) vient d'être utilisé dans le jeu, ou le
     * téléphone savait qu'il l'était déjà (invité qui s'est connecté ensuite). Idempotent.
     */
    public function useFreeHint(Request $request)
    {
        $product = $this->akwaba->product();
        abort_unless($product, 404);

        $this->akwaba->markFreeHintUsed($request->user(), $product);

        return response()->json(['freeUsed' => true]);
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
     * POST /jeux/akwaba/buy  {product: 'full'|'hints5', classic?: 1}
     * Renvoie l'URL de paiement : checkout Mobile Money personnalisé si CHECKOUT_MM_AKWABA est
     * activé, sinon (ou avec classic=1, secours) la page Paystack hébergée, Mobile Money seulement.
     */
    public function buy(Request $request, AkwabaPurchaseService $purchases, PaystackMobileMoneyCheckout $mm)
    {
        $data = $request->validate([
            'product' => ['required', 'string', 'in:' . implode(',', AkwabaPurchaseService::PRODUCTS)],
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
        }

        if ($mm->shouldIntercept('akwaba', $request)) {
            return response()->json(['url' => $mm->entryUrl('akwaba', ['product' => $data['product']])]);
        }

        $url = $purchases->startHosted($data['product'], $user, $product, $request);

        if (!$url) {
            return response()->json(['error' => "Impossible d'initialiser le paiement."], 502);
        }

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

        // Route publique qui déclenche un appel à l'API Paystack : référence au format attendu
        // seulement (et throttle sur la route), le reste repart vers le jeu sans appel
        if (!preg_match('/^[A-Za-z0-9-]{1,120}$/', $reference)) {
            return redirect()->route('akwaba.show', ['paid' => 1]);
        }

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
