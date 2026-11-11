<?php

namespace App\Http\Controllers;

use App\Services\PacteService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

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
     * GET /jeux/le-pacte/v/{version}/index.html
     * Le jeu tel quel. Cache long seulement sous la version courante ; une autre version
     * (ancienne page encore ouverte) est servie quand même, mais toujours revalidée.
     */
    public function file(string $version)
    {
        $path = $this->gamePath();
        abort_unless(is_file($path), 404);

        // Pas de CSP restrictive sur le jeu lui-même (scripts inline, Google Fonts, Web Audio) :
        // seulement l'interdiction d'être affiché dans un cadre d'un autre site.
        return response()->file($path, [
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
        $key   = implode('|', array_map(fn ($p) => basename($p) . '.' . filemtime($p) . '-' . filesize($p), $paths));

        return $this->version = Cache::rememberForever(
            'pacte.file.version.' . md5($key),
            fn () => substr(hash('sha256', implode('', array_map(fn ($p) => hash_file('sha256', $p), $paths))), 0, 16)
        );
    }
}
