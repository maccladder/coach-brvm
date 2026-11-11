<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Services\PacteService;
use App\Services\Payments\PactePurchaseService;
use App\Services\Payments\PaystackMarketplaceService;
use App\Services\Payments\PaystackMobileMoneyCheckout;
use App\Services\Payments\PaystackPacteHintsService;
use App\Services\PaystackService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Le Pacte avec les caïmans : page hôte publique (/jeux/le-pacte) et fichiers du jeu.
 *
 * Le jeu est un dossier (index.html + img/) qui charge ses images par chemins relatifs
 * « img/... » (CSS et JavaScript). Il est servi sous une adresse versionnée,
 * /jeux/le-pacte/v/{version}/index.html : les chemins relatifs pointent alors d'eux-mêmes vers
 * /jeux/le-pacte/v/{version}/img/..., servis par la route image(). Rien n'est réécrit dans le jeu.
 * Les deux routes de fichiers tournent sans session ni cookies (cf. routes/web.php).
 */
class PacteController extends Controller
{
    /** Version calculée une fois par requête (chaque image la compare). */
    private ?string $version = null;

    public function __construct(private PacteService $pacte) {}

    /**
     * GET /jeux/le-pacte
     * Page hôte publique : tout le monde joue au prologue et aux chapitres 1-2 sans compte.
     */
    public function show(Request $request)
    {
        abort_unless(is_file($this->gamePath()), 404);

        $nonce = base64_encode(random_bytes(16));

        return response()
            ->view('pacte-play', [
                'product'  => $this->pacte->product(),
                'version'  => $this->fileVersion(),
                'paid'     => $request->boolean('paid'),
                'whatsapp' => (string) config('checkout.whatsapp'),
                'nonce'    => $nonce,
                // Navigateur intégré (pub Facebook…) : bandeau « ouvre le jeu dans ton navigateur »
                'inApp'    => PacteService::inAppBrowser($request->userAgent()),
            ])
            // Même politique qu'Akwaba : scripts de la page (nonce) et pixel Meta s'il est configuré
            ->header('Content-Security-Policy', implode('; ', [
                "default-src 'self'",
                trim("script-src 'nonce-{$nonce}' " . $this->trackingSources('script')),
                "style-src 'self' 'unsafe-inline'",
                trim("img-src 'self' data: " . $this->trackingSources('img')),
                trim("connect-src 'self' " . $this->trackingSources('connect')),
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

    private function trackingSources(string $directive): string
    {
        if (empty(config('services.facebook_pixel.id'))) {
            return '';
        }

        return match ($directive) {
            'script'  => 'https://connect.facebook.net',
            'img'     => 'https://www.facebook.com',
            'connect' => 'https://www.facebook.com https://connect.facebook.net',
            default   => '',
        };
    }

    /**
     * GET /jeux/le-pacte/connexion
     * Connexion demandée au moment de payer : retour sur le jeu après login (formulaire ou Google).
     */
    public function login(Request $request)
    {
        if ($request->user()) {
            return redirect()->route('pacte.show');
        }

        redirect()->setIntendedUrl(route('pacte.show'));

        return redirect()->route('login');
    }

    /**
     * Ajouté en tête du jeu servi, donc avant son script : le jeu lit window.PacteHost au
     * chargement. Il reprend celui de la page hôte (/jeux/le-pacte, même origine). Ouvert seul,
     * hors de la page, il y renvoie : sans pont, le jeu tournerait en mode test (suite gratuite).
     */
    public const HOST_BRIDGE = '<script>(function(){try{var p=window.parent;'
        . 'if(p!==window&&p.PacteHost){window.PacteHost=p.PacteHost;return;}}catch(e){}'
        . "if(window.top===window)location.replace('/jeux/le-pacte');})();</script>\n";

    /**
     * GET /jeux/le-pacte/v/{version}/index.html
     * Le jeu, précédé du pont HOST_BRIDGE. Cache long seulement sous la version courante ; une
     * autre version (ancienne page encore ouverte) est servie quand même, mais revalidée.
     */
    public function file(string $version)
    {
        $path = $this->gamePath();
        abort_unless(is_file($path), 404);

        // Pas de CSP restrictive sur le jeu lui-même (scripts inline, Google Fonts, Web Audio) :
        // seulement l'interdiction d'être affiché dans un cadre d'un autre site.
        return response(self::HOST_BRIDGE . file_get_contents($path), 200, [
            'Content-Type'            => 'text/html; charset=UTF-8',
            'Cache-Control'           => $this->cacheControl($version),
            'Content-Security-Policy' => "frame-ancestors 'self'",
            'X-Frame-Options'         => 'SAMEORIGIN',
            'X-Content-Type-Options'  => 'nosniff',
            'Referrer-Policy'         => 'same-origin',
        ]);
    }

    /**
     * GET /jeux/le-pacte/v/{version}/img/{file}
     * Images du jeu, appelées par les chemins relatifs « img/... » du HTML, du CSS et du JS.
     * Nom de fichier limité par la route à [A-Za-z0-9_-]+.(jpg|png) : pas de sous-dossier.
     */
    public function image(string $version, string $file)
    {
        $path = $this->imgDir() . DIRECTORY_SEPARATOR . $file;
        abort_unless(is_file($path), 404);

        return response()->file($path, [
            'Content-Type'           => str_ends_with($file, '.png') ? 'image/png' : 'image/jpeg',
            'Cache-Control'          => $this->cacheControl($version),
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    // ─── API de la page hôte (pont window.PacteHost) ───────────────────────

    /**
     * Corps JSON tel qu'envoyé : TrimStrings et ConvertEmptyStringsToNull modifieraient les
     * textes de l'état du jeu, qui doit être stocké tel quel.
     */
    private function rawJson(Request $request): array
    {
        $data = json_decode($request->getContent(), true);

        return is_array($data) ? $data : [];
    }

    /**
     * GET /jeux/le-pacte/state
     */
    public function state(Request $request)
    {
        return response()->json($this->pacte->state($request->user()))
            ->header('Cache-Control', 'no-store');
    }

    /**
     * POST /jeux/le-pacte/event  {type: un nom de PacteService::EVENTS}
     * Compteur anonyme de l'entonnoir, invités et connectés (une ligne par session et par type).
     */
    public function event(Request $request)
    {
        $data = $request->validate([
            'type' => ['required', 'string', 'in:' . implode(',', PacteService::EVENTS)],
        ]);

        $this->pacte->recordEvent($data['type'], $request->session()->getId(), $request->userAgent());

        return response()->noContent();
    }

    /**
     * POST /jeux/le-pacte/progress  {save: {point, etat} | null}
     */
    public function progress(Request $request)
    {
        $product = $this->pacte->product();
        abort_unless($product, 404);

        // Objets PHP (et non tableaux) : {} et [] restent distincts dans l'état réencodé
        $body = json_decode($request->getContent());

        if (!$body instanceof \stdClass || !property_exists($body, 'save')) {
            return response()->json(['error' => 'Sauvegarde absente'], 422);
        }

        $save = $body->save;

        if ($save !== null) {
            if (!$save instanceof \stdClass
                || !is_string($save->point ?? null)
                || !in_array($save->point, PacteService::POINTS, true)
                || !(($save->etat ?? null) instanceof \stdClass)) {
                return response()->json(['error' => 'Sauvegarde invalide'], 422);
            }

            $etat = json_encode($save->etat, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRESERVE_ZERO_FRACTION);

            if ($etat === false || strlen($etat) > PacteService::MAX_ETAT_BYTES) {
                return response()->json(['error' => 'Sauvegarde trop volumineuse'], 422);
            }

            // Sans déblocage, seule la partie gratuite est atteignable
            if (!$this->pacte->isFreePoint($save->point) && !$this->pacte->isUnlocked($request->user(), $product)) {
                return response()->json(['error' => 'Histoire non débloquée'], 403);
            }

            $save = ['point' => $save->point, 'etat' => $etat];
        }

        $this->pacte->saveGame($request->user(), $product, $save);

        return response()->json(['saved' => true]);
    }

    /**
     * POST /jeux/le-pacte/fin  {cle, choix: [...]}
     * Fin obtenue (statistiques). Les fins sont dans la partie payante.
     */
    public function fin(Request $request)
    {
        $product = $this->pacte->product();
        abort_unless($product, 404);

        $body  = $this->rawJson($request);
        $cle   = $body['cle'] ?? null;
        $choix = $body['choix'] ?? [];

        if (!is_string($cle) || !in_array($cle, PacteService::FINS, true)
            || !is_array($choix) || !array_is_list($choix)
            || strlen(json_encode($choix)) > PacteService::MAX_CHOIX_BYTES) {
            return response()->json(['error' => 'Fin invalide'], 422);
        }

        if (!$this->pacte->isUnlocked($request->user(), $product)) {
            return response()->json(['error' => 'Histoire non débloquée'], 403);
        }

        $this->pacte->recordFin($request->user(), $product, $cle, $choix);

        return response()->json(['recorded' => true]);
    }

    /**
     * POST /jeux/le-pacte/hint/use
     * Consomme un indice (le gratuit d'abord) et renvoie le nombre d'indices restants.
     */
    public function useHint(Request $request)
    {
        $product = $this->pacte->product();
        abort_unless($product, 404);

        return response()->json($this->pacte->consumeHint($request->user(), $product));
    }

    /**
     * POST /jeux/le-pacte/hint/free
     * L'invité avait déjà utilisé son indice gratuit sur ce téléphone avant de se connecter
     * (localStorage « pacte-caimans-indices » à 0) : il n'en retrouve pas un second. Idempotent.
     */
    public function useFreeHint(Request $request)
    {
        $product = $this->pacte->product();
        abort_unless($product, 404);

        $this->pacte->markFreeHintUsed($request->user(), $product);

        return response()->json(['freeUsed' => true]);
    }

    /**
     * POST /jeux/le-pacte/buy  {product: 'full'|'hints5', classic?: 1}
     * Renvoie l'URL de paiement : checkout Mobile Money si CHECKOUT_MM_PACTE est activé, sinon
     * (ou avec classic=1) la page Paystack hébergée, Mobile Money seulement. Comme Akwaba.
     */
    public function buy(Request $request, PactePurchaseService $purchases, PaystackMobileMoneyCheckout $mm)
    {
        $data = $request->validate([
            'product' => ['required', 'string', 'in:' . implode(',', PactePurchaseService::PRODUCTS)],
        ]);

        $product = $this->pacte->product();
        abort_unless($product, 404);

        $user = $request->user();

        if ($data['product'] === 'full') {
            if ($this->pacte->isUnlocked($user, $product)) {
                return response()->json(['error' => 'Histoire déjà débloquée', 'already' => true], 409);
            }

            if ((int) $product->price <= 0) {
                Log::error('Le Pacte buy: prix du produit invalide', ['product_id' => $product->id]);
                return response()->json(['error' => 'Produit indisponible'], 503);
            }
        }

        if ($mm->shouldIntercept('pacte', $request)) {
            return response()->json(['url' => $mm->entryUrl('pacte', ['product' => $data['product']])]);
        }

        $url = $purchases->startHosted($data['product'], $user, $product, $request);

        if (!$url) {
            return response()->json(['error' => "Impossible d'initialiser le paiement."], 502);
        }

        return response()->json(['url' => $url]);
    }

    /**
     * GET /jeux/le-pacte/paiement/retour?reference=…
     * Retour Paystack (filet si le webhook tarde ; il reste la source de vérité). Idempotent.
     */
    public function paymentReturn(Request $request, PaystackService $paystack, PaystackMarketplaceService $marketplace, PaystackPacteHintsService $hints)
    {
        $reference = (string) ($request->query('reference') ?: $request->query('trxref', ''));

        // Route publique qui appelle l'API Paystack : référence au format attendu seulement
        if (!preg_match('/^[A-Za-z0-9-]{1,120}$/', $reference)) {
            return redirect()->route('pacte.show', ['paid' => 1]);
        }

        if (str_starts_with($reference, PaystackPacteHintsService::PREFIX)) {
            $outcome = $hints->handle($reference);
            Log::info('Le Pacte retour hints', ['reference' => $reference, 'outcome' => $outcome]);
        } elseif (str_starts_with($reference, 'MP-')) {
            $isPacte = data_get(Payment::where('transaction_id', $reference)->first()?->meta, 'product_slug') === PacteService::SLUG;
            $data    = $isPacte ? $paystack->verify($reference) : null;

            if ($data && ($data['status'] ?? null) === 'success') {
                $outcome = $marketplace->handleChargeSuccess($data);
                Log::info('Le Pacte retour full', ['reference' => $reference, 'outcome' => $outcome]);
            }
        }

        return redirect()->route('pacte.show', ['paid' => 1]);
    }

    // ─── Fichiers du jeu ───────────────────────────────────────────────────

    private function cacheControl(string $version): string
    {
        return $version === $this->fileVersion()
            ? 'public, max-age=31536000, immutable'
            : 'no-cache';
    }

    private function gamePath(): string
    {
        return resource_path('games/le-pacte/index.html');
    }

    private function imgDir(): string
    {
        return resource_path('games/le-pacte/img');
    }

    /** index.html puis les images, dans un ordre stable. */
    private function gameFiles(): array
    {
        $images = glob($this->imgDir() . DIRECTORY_SEPARATOR . '*.{jpg,png}', GLOB_BRACE) ?: [];
        sort($images);

        return array_values(array_filter([$this->gamePath(), ...$images], 'is_file'));
    }

    /**
     * Version commune au jeu et à ses images : hash de tous les fichiers, recalculé seulement
     * quand l'un d'eux change (date ou taille), ou quand une image est ajoutée ou retirée.
     */
    private function fileVersion(): string
    {
        if ($this->version !== null) {
            return $this->version;
        }

        $paths = $this->gameFiles();
        $key   = md5(self::HOST_BRIDGE) . '|'
            . implode('|', array_map(fn ($p) => basename($p) . '.' . filemtime($p) . '-' . filesize($p), $paths));

        // Le pont ajouté au jeu compte aussi : le changer change l'adresse
        return $this->version = Cache::rememberForever(
            'pacte.file.version.' . md5($key),
            fn () => substr(hash('sha256', hash('sha256', self::HOST_BRIDGE)
                . implode('', array_map(fn ($p) => hash_file('sha256', $p), $paths))), 0, 16)
        );
    }
}
