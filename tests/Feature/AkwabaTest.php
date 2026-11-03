<?php

namespace Tests\Feature;

use App\Models\AdminGrant;
use App\Models\GameCredit;
use App\Models\GameCreditLedger;
use App\Models\GameProgress;
use App\Models\MarketplaceProduct;
use App\Models\Payment;
use App\Models\User;
use App\Services\AkwabaService;
use App\Services\PaystackService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class AkwabaTest extends TestCase
{
    use RefreshDatabase;

    private const SECRET = 'sk_test_webhook_secret';

    private MarketplaceProduct $product;
    private User $player;
    private object $paystack;

    protected function setUp(): void
    {
        parent::setUp();
        config(['services.paystack.secret' => self::SECRET]);
        Mail::fake();

        // Créé par la migration 2026_11_03_000003
        $this->product = MarketplaceProduct::where('slug', 'akwaba')->firstOrFail();
        $this->player  = User::factory()->create();

        // Faux Paystack : transactions renvoyées par verify(), appels à initialize() enregistrés
        $this->paystack = new class extends PaystackService {
            public array $transactions = [];
            public array $initialized = [];
            public array $verified = [];

            public function __construct() {}

            public function initialize(array $data): ?string
            {
                $this->initialized[] = $data;
                return 'https://checkout.paystack.com/' . $data['reference'];
            }

            public function verify(string $reference): ?array
            {
                $this->verified[] = $reference;

                return isset($this->transactions[$reference])
                    ? ['reference' => $reference] + $this->transactions[$reference]
                    : null;
            }
        };
        $this->app->instance(PaystackService::class, $this->paystack);
    }

    // ─── Helpers ────────────────────────────────────────────────────────────

    private function paystackHas(string $reference, int $amountXof, array $metadata, string $status = 'success', string $currency = 'XOF'): void
    {
        $this->paystack->transactions[$reference] = [
            'status'   => $status,
            'amount'   => $amountXof * 100,
            'currency' => $currency,
            'channel'  => 'mobile_money',
            'metadata' => $metadata,
        ];
    }

    private function hintsMetadata(?User $user = null): array
    {
        return ['user_id' => ($user ?? $this->player)->id, 'game' => 'akwaba', 'product' => 'hints5'];
    }

    private function postWebhook(string $reference, int $amountXof, ?string $signature = null)
    {
        $json = json_encode([
            'event' => 'charge.success',
            'data'  => ['reference' => $reference, 'status' => 'success', 'amount' => $amountXof * 100, 'currency' => 'XOF'],
        ]);

        return $this->call('POST', '/paystack/webhook', [], [], [], [
            'CONTENT_TYPE'              => 'application/json',
            'HTTP_X_PAYSTACK_SIGNATURE' => $signature ?? hash_hmac('sha512', $json, self::SECRET),
        ], $json);
    }

    private function balance(?User $user = null): int
    {
        return (int) GameCredit::where('user_id', ($user ?? $this->player)->id)
            ->where('game_slug', 'akwaba')->where('kind', 'hints')->value('balance');
    }

    private function giveHints(int $count, ?User $user = null): void
    {
        app(AkwabaService::class)->addHints($user ?? $this->player, $count, 'admin', null, null, 'test');
    }

    private function purchase(string $status = 'paid', ?User $user = null): void
    {
        DB::table('marketplace_purchases')->insert([
            'user_id'    => ($user ?? $this->player)->id,
            'product_id' => $this->product->id,
            'amount'     => 1000,
            'provider'   => 'paystack',
            'status'     => $status,
            'paid_at'    => $status === 'paid' ? now() : null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function state(?User $user = null)
    {
        return $user ? $this->actingAs($user)->getJson(route('akwaba.state')) : $this->getJson(route('akwaba.state'));
    }

    // ─── Fichier du jeu ─────────────────────────────────────────────────────

    /** Version du fichier telle que la page hôte la met dans l'URL de l'iframe. */
    private function iframeSrc(): string
    {
        preg_match('/<iframe[^>]+src="([^"]+)"/', $this->get(route('akwaba.show'))->getContent(), $m);

        return html_entity_decode($m[1] ?? '');
    }

    public function test_le_fichier_du_jeu_est_servi_tel_quel(): void
    {
        $response = $this->get($this->iframeSrc());

        $response->assertOk()
            ->assertHeader('X-Frame-Options', 'SAMEORIGIN')
            ->assertHeader('X-Content-Type-Options', 'nosniff');
        $this->assertStringStartsWith('text/html', $response->headers->get('Content-Type'));
        $this->assertSame(filesize(resource_path('games/akwaba.html')), $response->baseResponse->getFile()->getSize());
    }

    public function test_le_fichier_n_est_mis_en_cache_longtemps_que_sous_sa_version(): void
    {
        $src = $this->iframeSrc();
        $this->assertMatchesRegularExpression('#^/jeux/akwaba/fichier\?v=[0-9a-f]{16}$#', $src);

        $cache = fn (string $url) => $this->get($url)->headers->get('Cache-Control');

        $this->assertStringContainsString('immutable', $cache($src));
        $this->assertStringContainsString('max-age=31536000', $cache($src));
        // Sans version ou avec une ancienne : revalidation à chaque fois
        $this->assertStringContainsString('no-cache', $cache(route('akwaba.file')));
        $this->assertStringContainsString('no-cache', $cache(route('akwaba.file', ['v' => '0000000000000000'])));
        $this->assertStringNotContainsString('immutable', $cache(route('akwaba.file')));
    }

    // ─── Page hôte ──────────────────────────────────────────────────────────

    public function test_la_page_hote_est_publique_et_sans_sandbox(): void
    {
        $html = $this->get(route('akwaba.show'))->assertOk()->getContent();

        $this->assertStringContainsString('id="game-frame"', $html);
        $this->assertStringContainsString('allow="autoplay; fullscreen"', $html);
        $this->assertStringNotContainsString('sandbox=', $html);
        $this->assertStringContainsString('name="csrf-token"', $html);
        $this->assertStringContainsString(json_encode(route('akwaba.login', [], false)), $html);
        // Appels API relatifs : la session suit, que l'on arrive par boursiv.com ou www.
        $this->assertStringContainsString(json_encode(route('akwaba.state', [], false)), $html);
        $this->assertStringNotContainsString(json_encode(route('akwaba.state')), $html);
    }

    public function test_la_page_hote_a_une_csp_stricte_avec_nonce(): void
    {
        $response = $this->get(route('akwaba.show'))->assertOk();
        $csp = $response->headers->get('Content-Security-Policy');

        preg_match("/script-src 'nonce-([^']+)'/", $csp, $m);
        $this->assertNotEmpty($m[1] ?? null);
        $this->assertStringContainsString('<script nonce="' . $m[1] . '">', $response->getContent());
        // Un seul script, et c'est celui qui porte le nonce
        $this->assertSame(1, substr_count($response->getContent(), '<script'));
        $this->assertStringNotContainsString('unsafe-inline', explode(';', explode('script-src', $csp)[1])[0]);

        foreach (["default-src 'self'", "connect-src 'self'", "frame-src 'self'", "frame-ancestors 'self'", "object-src 'none'", "base-uri 'none'"] as $rule) {
            $this->assertStringContainsString($rule, $csp);
        }
        $response->assertHeader('X-Frame-Options', 'SAMEORIGIN')
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('Referrer-Policy', 'same-origin');

        // Nouveau nonce à chaque affichage
        $other = $this->get(route('akwaba.show'))->headers->get('Content-Security-Policy');
        $this->assertNotSame($csp, $other);
    }

    public function test_le_jeu_ne_peut_pas_etre_encadre_par_un_autre_site(): void
    {
        $this->get(route('akwaba.file'))
            ->assertHeader('Content-Security-Policy', "frame-ancestors 'self'")
            ->assertHeader('X-Frame-Options', 'SAMEORIGIN');
    }

    public function test_la_page_hote_est_indexable_avec_les_balises_de_partage(): void
    {
        $html = $this->get(route('akwaba.show'))->assertOk()->getContent();

        $this->assertStringNotContainsString('noindex', $html);

        $expected = [
            'property="og:title" content="Akwaba : le mystère de la lagune Ébrié"',
            'property="og:description" content="Un jeu d&#039;énigmes à Abidjan. Gratuit pour commencer."',
            'property="og:image" content="https://boursiv.com/img/jeux/akwaba-partage.jpg"',
            'property="og:url" content="https://boursiv.com/jeux/akwaba"',
            'property="og:image:width" content="1200"',
            'property="og:image:height" content="630"',
            'name="twitter:card" content="summary_large_image"',
            'name="twitter:image" content="https://boursiv.com/img/jeux/akwaba-partage.jpg"',
            'rel="canonical" href="https://boursiv.com/jeux/akwaba"',
        ];
        foreach ($expected as $tag) {
            $this->assertStringContainsString(html_entity_decode($tag, ENT_QUOTES), html_entity_decode($html, ENT_QUOTES), $tag);
        }
    }

    public function test_l_image_de_partage_est_dans_le_depot_au_bon_format(): void
    {
        $path = public_path('img/jeux/akwaba-partage.jpg');

        $this->assertFileExists($path);
        [$width, $height, $type] = getimagesize($path);
        $this->assertSame([1200, 630, IMAGETYPE_JPEG], [$width, $height, $type]);
    }

    public function test_le_produit_est_publie_avec_sa_couverture(): void
    {
        $this->assertSame('published', $this->product->status);
        $this->assertSame('img/jeux/akwaba-partage.jpg', $this->product->cover_image_path);
        $this->assertSame(asset('img/jeux/akwaba-partage.jpg'), $this->product->cover_url);
        // Couverture du dépôt : servie telle quelle comme miniature (pas de génération sur storage/)
        $this->assertSame(asset('img/jeux/akwaba-partage.jpg'), $this->product->vignette_url);
    }

    public function test_la_couverture_s_affiche_sur_les_cartes_et_la_fiche(): void
    {
        $cover = asset('img/jeux/akwaba-partage.jpg');

        $this->get(route('marketplace.index', ['type' => 'game']))->assertOk()->assertSee($cover, false);
        $this->get(route('marketplace.show', 'akwaba'))->assertOk()->assertSee($cover, false);

        $this->purchase();
        $this->actingAs($this->player)->get(route('my.products'))->assertOk()->assertSee($cover, false);
    }

    public function test_les_couvertures_televersees_restent_sur_storage(): void
    {
        $other = MarketplaceProduct::create([
            'title' => 'Autre jeu', 'slug' => 'autre-jeu', 'type' => 'game', 'status' => 'published',
            'price' => 500, 'cover_image_path' => 'marketplace/covers/autre.jpg',
        ]);

        $this->assertSame(asset('storage/marketplace/covers/autre.jpg'), $other->cover_url);
    }

    public function test_connexion_depuis_le_cadenas_ramene_au_jeu(): void
    {
        $this->get(route('akwaba.login'))
            ->assertRedirect(route('login'))
            ->assertSessionHas('url.intended', route('akwaba.show'));

        $this->actingAs($this->player)->get(route('akwaba.login'))->assertRedirect(route('akwaba.show'));
    }

    // ─── Compteur anonyme des invités ───────────────────────────────────────

    /** Navigateur invité : renvoie toujours le même cookie de session, comme un vrai navigateur. */
    private function guestBrowser(string $sessionId)
    {
        // withCredentials : sans lui, les requêtes JSON de test n'envoient aucun cookie
        return $this->withCredentials()->withCookie(config('session.cookie'), $sessionId);
    }

    public function test_evenements_invites_une_fois_par_session_et_par_type_sans_donnee_personnelle(): void
    {
        $sessionId = str_repeat('a', 40);

        $this->guestBrowser($sessionId)->postJson(route('akwaba.event'), ['type' => 'play_start'])->assertNoContent();
        $this->guestBrowser($sessionId)->postJson(route('akwaba.event'), ['type' => 'play_start'])->assertNoContent();
        $this->guestBrowser($sessionId)->postJson(route('akwaba.event'), ['type' => 'lock_reached'])->assertNoContent();

        $this->assertSame(1, DB::table('akwaba_events')->where('type', 'play_start')->count());
        $this->assertSame(1, DB::table('akwaba_events')->where('type', 'lock_reached')->count());

        // Ni id de session en clair, ni IP : seulement type, HMAC de session et date
        $row = (array) DB::table('akwaba_events')->first();
        $this->assertSame(['id', 'type', 'session_hash', 'created_at'], array_keys($row));
        $this->assertNotSame($sessionId, $row['session_hash']);
        $this->assertSame(hash_hmac('sha256', $sessionId, config('app.key')), $row['session_hash']);
        $this->assertStringNotContainsString('127.0.0.1', json_encode(DB::table('akwaba_events')->get()));
    }

    public function test_evenements_une_autre_session_compte_a_part(): void
    {
        $this->guestBrowser(str_repeat('a', 40))->postJson(route('akwaba.event'), ['type' => 'play_start'])->assertNoContent();
        $this->guestBrowser(str_repeat('b', 40))->postJson(route('akwaba.event'), ['type' => 'play_start'])->assertNoContent();

        $this->assertSame(2, DB::table('akwaba_events')->where('type', 'play_start')->count());
    }

    public function test_evenements_enregistres_aussi_pour_un_joueur_connecte(): void
    {
        $this->actingAs($this->player)->postJson(route('akwaba.event'), ['type' => 'lock_reached'])->assertNoContent();

        // Toujours anonyme : aucune colonne ne relie la ligne au compte
        $this->assertSame(1, DB::table('akwaba_events')->where('type', 'lock_reached')->count());
        $this->assertSame(['id', 'type', 'session_hash', 'created_at'], array_keys((array) DB::table('akwaba_events')->first()));
    }

    public function test_evenements_liste_blanche_stricte(): void
    {
        $allowed = [
            'page_open', 'page_loaded', 'play_start', 'new_game', 'continue', 'ch1_reached', 'ch2_reached',
            'lock_reached', 'free_hint', 'shop_open', 'paywall_open', 'buy_click_full', 'buy_click_hints5',
        ];
        $this->assertSame($allowed, AkwabaService::EVENTS);

        $sessionId = str_repeat('w', 40);
        foreach ($allowed as $type) {
            $this->guestBrowser($sessionId)->postJson(route('akwaba.event'), ['type' => $type])->assertNoContent();
        }
        $this->assertSame(count($allowed), DB::table('akwaba_events')->count());

        foreach (['achat', 'buy_click_vip', 'PAGE_OPEN', "page_open'--", 'page_open;', str_repeat('x', 300)] as $bad) {
            $this->guestBrowser($sessionId)->postJson(route('akwaba.event'), ['type' => $bad])->assertStatus(422);
        }
        // Espaces autour : retirés par TrimStrings, donc le même événement, déjà compté
        $this->guestBrowser($sessionId)->postJson(route('akwaba.event'), ['type' => ' page_open '])->assertNoContent();
        $this->guestBrowser($sessionId)->postJson(route('akwaba.event'), [])->assertStatus(422);
        $this->guestBrowser($sessionId)->postJson(route('akwaba.event'), ['type' => ['page_open']])->assertStatus(422);

        $this->assertSame(count($allowed), DB::table('akwaba_events')->count());
    }

    public function test_evenements_limites_par_session(): void
    {
        $sessionId = str_repeat('c', 40);
        for ($i = 0; $i < 30; $i++) {
            $this->guestBrowser($sessionId)->postJson(route('akwaba.event'), ['type' => 'page_open'])->assertNoContent();
        }

        $this->guestBrowser($sessionId)->postJson(route('akwaba.event'), ['type' => 'page_open'])->assertStatus(429);
        // Une autre session sur la même IP n'est pas bloquée (CGNAT des réseaux mobiles)
        $this->guestBrowser(str_repeat('d', 40))->postJson(route('akwaba.event'), ['type' => 'page_open'])->assertNoContent();
    }

    public function test_la_page_hote_relaie_les_evenements_de_l_entonnoir(): void
    {
        $html = $this->get(route('akwaba.show'))->getContent();

        $this->assertStringContainsString(json_encode(route('akwaba.event', [], false)), $html);
        // Même liste blanche que le serveur pour les événements du jeu
        $this->assertStringContainsString(
            "const GAME_EVENTS = ['new_game', 'continue', 'free_hint', 'shop_open', 'paywall_open', 'buy_click_full', 'buy_click_hints5'];",
            $html
        );
        $this->assertStringContainsString("const SAVE_EVENTS = { '1': 'ch1_reached', '2': 'ch2_reached', '3': 'lock_reached' };", $html);
        $this->assertStringContainsString("case 'event':", $html);
        $this->assertStringContainsString('if (GAME_EVENTS.includes(data.name)) trackEvent(data.name);', $html);
        // page_open dès l'ouverture, page_loaded au ready (avec le retrait de l'écran de chargement)
        $this->assertStringContainsString("trackEvent('page_open');", $html);
        $this->assertStringContainsString("hideLoading();\n                    trackEvent('page_loaded');", $html);
        // Plus de restriction aux invités
        $this->assertStringNotContainsString('state.logged) return;\n            try {', $html);
    }

    public function test_ecran_de_chargement_par_dessus_le_jeu(): void
    {
        $html = $this->get(route('akwaba.show'))->getContent();

        $this->assertStringContainsString('id="ak-loading"', $html);
        $this->assertStringContainsString('background: #0b0d0a', $html);
        $this->assertStringContainsString('AKWABA', $html);
        $this->assertStringContainsString('le mystère de la lagune Ébrié', $html);
        $this->assertStringContainsString('Chargement du jeu…', $html);
        $this->assertStringContainsString('(le premier chargement peut prendre quelques secondes)', $html);
        $this->assertStringContainsString('class="ak-load-bar"', $html);
        // L'écran suit l'iframe dans le HTML : il est affiché au-dessus dès le premier rendu
        $this->assertGreaterThan(strpos($html, 'id="game-frame"'), strpos($html, 'id="ak-loading"'));
        // Secours si le jeu n'envoie jamais ready
        $this->assertStringContainsString("frame.addEventListener('load', armLoadingFallback);", $html);
        $this->assertStringContainsString("doc.readyState === 'complete' && doc.URL !== 'about:blank'", $html);
        $this->assertStringContainsString('setTimeout(hideLoading, 60000);', $html);
    }

    // ─── Marketplace : pas d'achat ni de téléchargement standard ────────────

    public function test_la_fiche_repassee_en_brouillon_redevient_invisible(): void
    {
        $this->product->update(['status' => 'draft']);

        $this->get(route('marketplace.show', 'akwaba'))->assertNotFound();
    }

    public function test_la_fiche_propose_jouer_gratuitement_au_lieu_d_acheter(): void
    {
        $this->product->update(['status' => 'published']);

        foreach ([null, $this->player] as $viewer) {
            $response = $viewer ? $this->actingAs($viewer)->get(route('marketplace.show', 'akwaba')) : $this->get(route('marketplace.show', 'akwaba'));

            $response->assertOk()
                ->assertSee('Jouer gratuitement')
                ->assertSee('Gratuit pour commencer')
                ->assertSee('Histoire complète 1 000 FCFA')
                ->assertSee(route('akwaba.show'), false)
                ->assertDontSee(route('paystack.marketplace.buy', $this->product), false)
                ->assertDontSee('Se connecter pour acheter')
                ->assertDontSee('Payer maintenant')
                ->assertDontSee(route('my.products.download', $this->product), false);
        }
    }

    public function test_la_carte_marketplace_et_jeux_renvoie_vers_le_jeu(): void
    {
        $this->product->update(['status' => 'published']);

        $this->get(route('marketplace.index', ['type' => 'game']))
            ->assertOk()
            ->assertSee('Gratuit pour commencer')
            ->assertSee('Histoire complète 1 000 FCFA')
            ->assertSee(route('akwaba.show'), false);
    }

    public function test_les_routes_marketplace_standard_ne_servent_jamais_le_jeu(): void
    {
        $this->purchase();
        $this->actingAs($this->player);

        $this->get(route('my.products.play', $this->product))->assertRedirect(route('akwaba.show'));
        $this->get(route('my.products.download', $this->product))->assertRedirect(route('akwaba.show'));
        $this->get(route('my.products.game-html', $this->product))->assertNotFound();

        // Même si un game_html était téléversé par erreur depuis l'admin
        $this->product->update(['game_html' => '<html>copie</html>']);
        $this->get(route('my.products.game-html', $this->product))->assertNotFound();

        $this->get(route('my.products'))->assertOk()->assertSee(route('akwaba.show'), false);
    }

    // ─── State ──────────────────────────────────────────────────────────────

    public function test_state_invite(): void
    {
        $this->state()->assertOk()->assertExactJson([
            'logged'   => false,
            'unlocked' => false,
            'hints'    => 0,
            'freeUsed' => false,
            'progress' => ['save' => null, 'fins' => []],
        ]);
    }

    public function test_state_connecte_sans_achat(): void
    {
        $this->state($this->player)->assertOk()->assertExactJson([
            'logged'   => true,
            'unlocked' => false,
            'hints'    => 0,
            'freeUsed' => false,
            'progress' => ['save' => null, 'fins' => []],
        ]);
    }

    public function test_state_connecte_avec_achat_indices_et_progression(): void
    {
        $this->purchase();
        $this->giveHints(4);
        GameProgress::create([
            'user_id'    => $this->player->id,
            'product_id' => $this->product->id,
            'data'       => ['save' => '5', 'fins' => ['oubli'], 'max' => '6'],
        ]);

        $this->state($this->player)->assertOk()->assertExactJson([
            'logged'   => true,
            'unlocked' => true,
            'hints'    => 4,
            'freeUsed' => false,
            'progress' => ['save' => '5', 'fins' => ['oubli']],
        ]);
    }

    // ─── Indice gratuit (un seul par joueur, pour toujours) ──────────────────

    public function test_indice_gratuit_marque_une_seule_fois_et_renvoye_dans_state(): void
    {
        $this->state($this->player)->assertJsonPath('freeUsed', false);

        $this->actingAs($this->player)->postJson(route('akwaba.hint.free'))->assertOk()->assertExactJson(['freeUsed' => true]);
        $first = GameProgress::where('user_id', $this->player->id)->value('free_hint_used_at');
        $this->assertNotNull($first);

        // Deuxième appel (autre téléphone, invité connecté ensuite…) : rien ne change
        $this->travel(5)->minutes();
        $this->actingAs($this->player)->postJson(route('akwaba.hint.free'))->assertOk()->assertExactJson(['freeUsed' => true]);

        $this->assertEquals($first, GameProgress::where('user_id', $this->player->id)->value('free_hint_used_at'));
        $this->assertSame(1, GameProgress::where('user_id', $this->player->id)->count());
        $this->state($this->player)->assertJsonPath('freeUsed', true);
    }

    public function test_indice_gratuit_ne_touche_ni_la_progression_ni_le_solde(): void
    {
        $this->purchase();
        $this->giveHints(3);
        $this->actingAs($this->player)->postJson(route('akwaba.progress'), ['save' => '4', 'fins' => ['oubli']])->assertOk();

        $this->actingAs($this->player)->postJson(route('akwaba.hint.free'))->assertOk();
        // Une sauvegarde de progression ensuite ne remet pas l'indice gratuit à zéro
        $this->actingAs($this->player)->postJson(route('akwaba.progress'), ['save' => '5', 'fins' => []])->assertOk();

        $this->state($this->player)
            ->assertJsonPath('freeUsed', true)
            ->assertJsonPath('hints', 3)
            ->assertJsonPath('progress', ['save' => '5', 'fins' => ['oubli']]);
        $this->assertSame(0, GameCreditLedger::where('reason', 'use')->count());
    }

    public function test_indice_gratuit_refuse_a_un_invite(): void
    {
        $this->postJson(route('akwaba.hint.free'))->assertUnauthorized();

        $this->assertSame(0, GameProgress::count());
        $this->state()->assertJsonPath('freeUsed', false);
    }

    public function test_indice_gratuit_isole_entre_joueurs(): void
    {
        $other = User::factory()->create();

        $this->actingAs($other)->postJson(route('akwaba.hint.free'))->assertOk();

        $this->state($other)->assertJsonPath('freeUsed', true);
        $this->state($this->player)->assertJsonPath('freeUsed', false);
    }

    public function test_indice_gratuit_limite(): void
    {
        for ($i = 0; $i < 10; $i++) {
            $this->actingAs($this->player)->postJson(route('akwaba.hint.free'))->assertOk();
        }

        $this->actingAs($this->player)->postJson(route('akwaba.hint.free'))->assertStatus(429);
    }

    public function test_la_page_hote_relaie_l_indice_gratuit(): void
    {
        $html = $this->get(route('akwaba.show'))->getContent();

        $this->assertStringContainsString(json_encode(route('akwaba.hint.free', [], false)), $html);
        $this->assertStringContainsString("case 'freeHintUsed':", $html);
        $this->assertStringContainsString('freeUsed: !!state.freeUsed', $html);
        // Invité : rien n'est envoyé (le jeu le garde sur le téléphone)
        $this->assertStringContainsString('if (!state || !state.logged || state.freeUsed || freeHintSent) return;', $html);
    }

    public function test_state_achat_en_attente_ne_debloque_pas(): void
    {
        $this->purchase('pending');

        $this->state($this->player)->assertJsonPath('unlocked', false);
    }

    public function test_state_attribution_admin_debloque_et_revocation_rebloque(): void
    {
        $grant = AdminGrant::create([
            'user_id'        => $this->player->id,
            'grantable_type' => MarketplaceProduct::class,
            'grantable_id'   => $this->product->id,
            'reason'         => 'Paiement WhatsApp',
            'granted_at'     => now(),
        ]);

        $this->state($this->player)->assertJsonPath('unlocked', true);

        $grant->update(['revoked_at' => now()]);

        $this->state($this->player)->assertJsonPath('unlocked', false);
    }

    public function test_state_ne_montre_que_les_donnees_du_joueur_connecte(): void
    {
        $other = User::factory()->create();
        $this->purchase('paid', $other);
        $this->giveHints(9, $other);

        $this->state($this->player)->assertJsonPath('unlocked', false)->assertJsonPath('hints', 0);
    }

    // ─── Progression ────────────────────────────────────────────────────────

    public function test_progress_exige_une_connexion(): void
    {
        $this->postJson(route('akwaba.progress'), ['save' => '1', 'fins' => []])->assertUnauthorized();
    }

    public function test_progress_valide_strictement_les_entrees(): void
    {
        $this->actingAs($this->player);

        $this->postJson(route('akwaba.progress'), ['save' => '8', 'fins' => []])->assertStatus(422);
        $this->postJson(route('akwaba.progress'), ['save' => 'X', 'fins' => []])->assertStatus(422);
        $this->postJson(route('akwaba.progress'), ['fins' => []])->assertStatus(422);
        $this->postJson(route('akwaba.progress'), ['save' => '1', 'fins' => ['autre']])->assertStatus(422);
        $this->postJson(route('akwaba.progress'), ['save' => '1', 'fins' => 'oubli'])->assertStatus(422);
        $this->postJson(route('akwaba.progress'), ['save' => ['1'], 'fins' => []])->assertStatus(422);

        $this->assertSame(0, GameProgress::count());
    }

    public function test_progress_sans_deblocage_limite_aux_chapitres_gratuits(): void
    {
        $this->actingAs($this->player);

        $this->postJson(route('akwaba.progress'), ['save' => '2', 'fins' => []])->assertOk();
        $this->postJson(route('akwaba.progress'), ['save' => '3', 'fins' => []])->assertForbidden();
        $this->postJson(route('akwaba.progress'), ['save' => '2', 'fins' => ['oubli']])->assertForbidden();

        $this->assertSame(['save' => '2', 'fins' => []], app(AkwabaService::class)->progress($this->player));
    }

    public function test_progress_fusionne_les_fins_sans_jamais_en_retirer(): void
    {
        $this->purchase();
        $this->actingAs($this->player);

        $this->postJson(route('akwaba.progress'), ['save' => '7', 'fins' => ['oubli']])
            ->assertOk()->assertJsonPath('progress.fins', ['oubli']);

        // Nouvelle partie sur un autre téléphone : retour au prologue, fins locales vides
        $this->postJson(route('akwaba.progress'), ['save' => 'P', 'fins' => []])
            ->assertOk()->assertJsonPath('progress', ['save' => 'P', 'fins' => ['oubli']]);

        $this->postJson(route('akwaba.progress'), ['save' => null, 'fins' => ['gardien', 'oubli']])
            ->assertOk()->assertJsonPath('progress', ['save' => null, 'fins' => ['oubli', 'gardien']]);

        $data = GameProgress::where('user_id', $this->player->id)->where('product_id', $this->product->id)->value('data');
        $data = is_string($data) ? json_decode($data, true) : $data;
        $this->assertSame('7', $data['max']); // chapitre le plus loin, pour les stats
        $this->assertSame(1, GameProgress::count());
    }

    public function test_progress_d_un_joueur_n_affecte_pas_un_autre(): void
    {
        $other = User::factory()->create();

        $this->actingAs($this->player)->postJson(route('akwaba.progress'), ['save' => '2', 'fins' => []])->assertOk();
        $this->actingAs($other)->postJson(route('akwaba.progress'), ['save' => '1', 'fins' => []])->assertOk();

        $this->assertSame('2', app(AkwabaService::class)->progress($this->player)['save']);
        $this->assertSame('1', app(AkwabaService::class)->progress($other)['save']);
    }

    // ─── Indices ────────────────────────────────────────────────────────────

    public function test_hint_use_exige_une_connexion(): void
    {
        $this->postJson(route('akwaba.hint.use'), ['id' => 'n1'])->assertUnauthorized();
    }

    public function test_hint_use_avec_solde_zero_refuse(): void
    {
        $this->actingAs($this->player)->postJson(route('akwaba.hint.use'), ['id' => 'n1'])
            ->assertOk()->assertExactJson(['id' => 'n1', 'ok' => false, 'hints' => 0]);

        $this->assertSame(0, GameCreditLedger::where('reason', 'use')->count());
    }

    public function test_hint_use_consomme_un_indice_et_renvoie_le_nonce(): void
    {
        $this->giveHints(5);

        $this->actingAs($this->player)->postJson(route('akwaba.hint.use'), ['id' => 'abc-123'])
            ->assertOk()->assertExactJson(['id' => 'abc-123', 'ok' => true, 'hints' => 4]);

        $this->assertSame(1, GameCreditLedger::where('user_id', $this->player->id)->where('reason', 'use')->where('delta', -1)->count());
    }

    public function test_hint_use_en_rafale_ne_passe_jamais_sous_zero(): void
    {
        $this->giveHints(3);
        $this->actingAs($this->player);

        $results = [];
        for ($i = 0; $i < 10; $i++) {
            $results[] = $this->postJson(route('akwaba.hint.use'), ['id' => "n$i"])->json('ok');
        }

        $this->assertSame(3, count(array_filter($results)));
        $this->assertSame(0, $this->balance());
        $this->assertSame(3, GameCreditLedger::where('reason', 'use')->count());
    }

    public function test_le_decrement_est_conditionnel_au_niveau_sql(): void
    {
        // Simule deux requêtes qui ont lu « solde = 1 » en même temps : la seconde
        // mise à jour conditionnelle ne doit rien modifier.
        $this->giveHints(1);
        $service = app(AkwabaService::class);

        $this->assertTrue($service->useHint($this->player)['ok']);
        $this->assertFalse($service->useHint($this->player)['ok']);
        $this->assertSame(0, $this->balance());
    }

    public function test_hint_use_valide_le_nonce(): void
    {
        $this->giveHints(1);
        $this->actingAs($this->player);

        $this->postJson(route('akwaba.hint.use'), [])->assertStatus(422);
        $this->postJson(route('akwaba.hint.use'), ['id' => str_repeat('x', 65)])->assertStatus(422);

        $this->assertSame(1, $this->balance());
    }

    // ─── Achat ──────────────────────────────────────────────────────────────

    public function test_buy_exige_une_connexion_et_un_produit_connu(): void
    {
        $this->postJson(route('akwaba.buy'), ['product' => 'full'])->assertUnauthorized();
        $this->actingAs($this->player)->postJson(route('akwaba.buy'), ['product' => 'vip'])->assertStatus(422);

        $this->assertSame([], $this->paystack->initialized);
    }

    public function test_buy_full_deja_possede_refuse(): void
    {
        $this->purchase();

        $this->actingAs($this->player)->postJson(route('akwaba.buy'), ['product' => 'full'])
            ->assertStatus(409)->assertJsonPath('already', true);

        $this->assertSame([], $this->paystack->initialized);
    }

    public function test_buy_full_attribue_par_l_admin_refuse(): void
    {
        AdminGrant::create([
            'user_id' => $this->player->id, 'grantable_type' => MarketplaceProduct::class,
            'grantable_id' => $this->product->id, 'granted_at' => now(),
        ]);

        $this->actingAs($this->player)->postJson(route('akwaba.buy'), ['product' => 'full'])->assertStatus(409);
    }

    public function test_buy_full_initialise_un_achat_marketplace_au_prix_serveur(): void
    {
        $url = $this->actingAs($this->player)
            ->postJson(route('akwaba.buy'), ['product' => 'full', 'amount' => 1])
            ->assertOk()->json('url');

        $init = $this->paystack->initialized[0];
        $this->assertSame('https://checkout.paystack.com/' . $init['reference'], $url);
        $this->assertMatchesRegularExpression('/^MP-\d{14}-[A-Z0-9]{6}$/', $init['reference']);
        $this->assertSame(1000, $init['amount']);
        $this->assertSame('XOF', $init['currency']);
        $this->assertSame(['mobile_money'], $init['channels']);
        $this->assertSame(route('akwaba.payment.return'), $init['callback_url']);
        $this->assertSame($this->player->id, $init['metadata']['user_id']);
        $this->assertSame('akwaba', $init['metadata']['game']);
        $this->assertSame('full', $init['metadata']['product']);

        $payment = Payment::where('transaction_id', $init['reference'])->firstOrFail();
        $this->assertSame(1000, (int) $payment->amount_paid);
        $this->assertSame('marketplace', $payment->purpose);
        $this->assertSame($this->product->id, (int) $payment->meta['product_id']);
        $this->assertSame('pending', DB::table('marketplace_purchases')->where('user_id', $this->player->id)->value('status'));
    }

    public function test_buy_hints5_initialise_500_xof(): void
    {
        $this->actingAs($this->player)->postJson(route('akwaba.buy'), ['product' => 'hints5', 'amount' => 5])->assertOk();

        $init = $this->paystack->initialized[0];
        $this->assertStringStartsWith('AKW-H-' . $this->player->id . '-', $init['reference']);
        $this->assertSame(500, $init['amount']);
        $this->assertSame(['mobile_money'], $init['channels']);
        $this->assertSame($this->hintsMetadata(), $init['metadata']);
        $this->assertSame(0, Payment::count());
    }

    // ─── Webhook et retour : déblocage complet ──────────────────────────────

    public function test_webhook_full_debloque_l_histoire(): void
    {
        $this->actingAs($this->player)->postJson(route('akwaba.buy'), ['product' => 'full'])->assertOk();
        $reference = $this->paystack->initialized[0]['reference'];

        $this->postWebhook($reference, 1000)->assertOk();

        $this->state($this->player)->assertJsonPath('unlocked', true);
    }

    public function test_webhook_full_montant_falsifie_ne_debloque_pas(): void
    {
        $this->actingAs($this->player)->postJson(route('akwaba.buy'), ['product' => 'full'])->assertOk();
        $reference = $this->paystack->initialized[0]['reference'];

        $this->postWebhook($reference, 100)->assertOk();

        $this->state($this->player)->assertJsonPath('unlocked', false);
    }

    public function test_retour_full_debloque_puis_redirige_vers_le_jeu(): void
    {
        $this->actingAs($this->player)->postJson(route('akwaba.buy'), ['product' => 'full'])->assertOk();
        $reference = $this->paystack->initialized[0]['reference'];
        $this->paystackHas($reference, 1000, ['user_id' => $this->player->id, 'product_id' => $this->product->id]);

        $this->get(route('akwaba.payment.return', ['reference' => $reference]))->assertRedirect('/jeux/akwaba?paid=1');

        $this->state($this->player)->assertJsonPath('unlocked', true);
    }

    // ─── Webhook et retour : packs d'indices ────────────────────────────────

    public function test_webhook_hints_valide_credite_5(): void
    {
        $this->paystackHas('AKW-H-1-OK', 500, $this->hintsMetadata());

        $this->postWebhook('AKW-H-1-OK', 500)->assertOk();

        $this->assertSame(5, $this->balance());
        $this->assertDatabaseHas('game_credit_ledger', [
            'user_id' => $this->player->id, 'delta' => 5, 'reason' => 'purchase',
            'payment_reference' => 'AKW-H-1-OK', 'amount' => 500,
        ]);
    }

    public function test_webhook_hints_rejoue_ne_credite_qu_une_fois(): void
    {
        $this->paystackHas('AKW-H-1-REPLAY', 500, $this->hintsMetadata());

        $this->postWebhook('AKW-H-1-REPLAY', 500)->assertOk();
        $this->postWebhook('AKW-H-1-REPLAY', 500)->assertOk();
        $this->get(route('akwaba.payment.return', ['reference' => 'AKW-H-1-REPLAY']))->assertRedirect('/jeux/akwaba?paid=1');

        $this->assertSame(5, $this->balance());
        $this->assertSame(1, GameCreditLedger::where('payment_reference', 'AKW-H-1-REPLAY')->count());
    }

    public function test_webhook_hints_signature_invalide_refuse(): void
    {
        $this->paystackHas('AKW-H-1-SIG', 500, $this->hintsMetadata());

        $this->postWebhook('AKW-H-1-SIG', 500, 'bad-signature')->assertStatus(401);

        $this->assertSame(0, $this->balance());
    }

    public function test_webhook_hints_se_fie_a_paystack_et_pas_au_corps_recu(): void
    {
        // Corps du webhook : 500 F ; transaction réelle chez Paystack : 100 F
        $this->paystackHas('AKW-H-1-CHEAP', 100, $this->hintsMetadata());

        $this->postWebhook('AKW-H-1-CHEAP', 500)->assertOk();

        $this->assertSame(0, $this->balance());
    }

    public function test_webhook_hints_refuse_devise_statut_ou_metadonnees_invalides(): void
    {
        $this->paystackHas('AKW-H-1-USD', 500, $this->hintsMetadata(), 'success', 'USD');
        $this->paystackHas('AKW-H-1-FAIL', 500, $this->hintsMetadata(), 'failed');
        $this->paystackHas('AKW-H-1-GAME', 500, ['game' => 'autre'] + $this->hintsMetadata());
        $this->paystackHas('AKW-H-1-PROD', 500, ['product' => 'full'] + $this->hintsMetadata());
        $this->paystackHas('AKW-H-1-USER', 500, ['user_id' => 999999] + $this->hintsMetadata());

        foreach (['AKW-H-1-USD', 'AKW-H-1-FAIL', 'AKW-H-1-GAME', 'AKW-H-1-PROD', 'AKW-H-1-USER', 'AKW-H-1-UNKNOWN'] as $reference) {
            $this->postWebhook($reference, 500)->assertOk();
        }

        $this->assertSame(0, GameCreditLedger::count());
        $this->assertSame(0, GameCredit::sum('balance'));
    }

    public function test_retour_hints_credite_le_joueur_des_metadonnees(): void
    {
        $this->paystackHas('AKW-H-1-RET', 500, $this->hintsMetadata());

        // Même appelé par quelqu'un d'autre, le crédit va au joueur qui a payé
        $intruder = User::factory()->create();
        $this->actingAs($intruder)->get(route('akwaba.payment.return', ['reference' => 'AKW-H-1-RET']))
            ->assertRedirect('/jeux/akwaba?paid=1');

        $this->assertSame(5, $this->balance());
        $this->assertSame(0, $this->balance($intruder));
    }

    public function test_webhook_hints_refuse_un_montant_a_peine_inferieur(): void
    {
        // 499,51 F : un arrondi l'aurait accepté
        $this->paystackHas('AKW-H-1-ROUND', 500, $this->hintsMetadata());
        $this->paystack->transactions['AKW-H-1-ROUND']['amount'] = 49951;

        $this->postWebhook('AKW-H-1-ROUND', 500)->assertOk();

        $this->assertSame(0, $this->balance());
    }

    public function test_retour_ignore_une_reference_mal_formee_sans_appeler_paystack(): void
    {
        foreach (['', '../../x', 'AKW-H-1 OR 1=1', str_repeat('A', 121), 'AKW-H-<script>'] as $reference) {
            $this->get(route('akwaba.payment.return', ['reference' => $reference]))
                ->assertRedirect(route('akwaba.show', ['paid' => 1]));
        }

        $this->assertSame([], $this->paystack->verified);
    }

    public function test_retour_de_paiement_limite(): void
    {
        for ($i = 0; $i < 20; $i++) {
            $this->get(route('akwaba.payment.return', ['reference' => 'AKW-H-1-X' . $i]))->assertRedirect();
        }

        $this->get(route('akwaba.payment.return', ['reference' => 'AKW-H-1-LAST']))->assertStatus(429);
    }

    public function test_state_limite_par_session_et_non_par_ip(): void
    {
        $guest = fn (string $id) => $this->withCredentials()->withCookie(config('session.cookie'), $id);

        for ($i = 0; $i < 60; $i++) {
            $guest(str_repeat('e', 40))->getJson(route('akwaba.state'))->assertOk();
        }

        $guest(str_repeat('e', 40))->getJson(route('akwaba.state'))->assertStatus(429);
        // Autre joueur derrière la même IP (CGNAT) : pas bloqué
        $guest(str_repeat('f', 40))->getJson(route('akwaba.state'))->assertOk();
    }

    public function test_parcours_complet_achat_puis_utilisation(): void
    {
        $this->actingAs($this->player)->postJson(route('akwaba.buy'), ['product' => 'hints5'])->assertOk();
        $reference = $this->paystack->initialized[0]['reference'];
        $this->paystackHas($reference, 500, $this->paystack->initialized[0]['metadata']);

        $this->postWebhook($reference, 500)->assertOk();
        $this->state($this->player)->assertJsonPath('hints', 5);

        $this->actingAs($this->player)->postJson(route('akwaba.hint.use'), ['id' => 'x'])->assertJsonPath('hints', 4);
        $this->state($this->player)->assertJsonPath('hints', 4);
    }
}
