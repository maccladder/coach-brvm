<?php

namespace Tests\Feature;

use App\Models\MarketplaceProduct;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Le Pacte avec les caïmans : page hôte, fichiers du jeu servis sous /v/{version}/, catalogue.
 */
class PacteTest extends TestCase
{
    use RefreshDatabase;

    private MarketplaceProduct $product;
    private User $player;

    protected function setUp(): void
    {
        parent::setUp();

        // Créé par la migration 2026_11_11_000001
        $this->product = MarketplaceProduct::where('slug', 'le-pacte')->firstOrFail();
        $this->player  = User::factory()->create();
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

    /** Adresse de l'iframe telle que la page hôte la donne. */
    private function iframeSrc(): string
    {
        preg_match('/<iframe[^>]+src="([^"]+)"/', $this->get(route('pacte.show'))->getContent(), $m);

        return html_entity_decode($m[1] ?? '');
    }

    private function version(): string
    {
        preg_match('#/jeux/le-pacte/v/([a-f0-9]{16})/index\.html#', $this->iframeSrc(), $m);

        return $m[1] ?? '';
    }

    // ─── Produit ────────────────────────────────────────────────────────────

    public function test_le_produit_est_cree_par_la_migration(): void
    {
        $this->assertSame('Le Pacte avec les caïmans de Yamoussoukro', $this->product->title);
        $this->assertSame('game', $this->product->type);
        $this->assertSame(1000, (int) $this->product->price);
        $this->assertSame('published', $this->product->status);
        $this->assertNull($this->product->game_html);
        $this->assertStringContainsString('Ce que le lac reçoit, il ne le rend jamais.', $this->product->description);
        $this->assertSame('img/jeux/le-pacte/le-pacte-hero.jpg', $this->product->cover_image_path);
        $this->assertFileExists(public_path('img/jeux/le-pacte/le-pacte-hero.jpg'));
    }

    // ─── Page hôte ──────────────────────────────────────────────────────────

    public function test_la_page_est_publique_avec_csp_a_nonce(): void
    {
        $response = $this->get(route('pacte.show'));

        $response->assertOk()
            ->assertSee('Le Pacte avec les caïmans de Yamoussoukro')
            ->assertSee(route('pacte.login'), false)
            ->assertHeader('X-Frame-Options', 'SAMEORIGIN');

        $csp = $response->headers->get('Content-Security-Policy');
        $this->assertMatchesRegularExpression("/script-src 'nonce-[A-Za-z0-9+\\/=]+'/", $csp);
        $this->assertStringContainsString("frame-ancestors 'self'", $csp);
        $this->assertNotSame('', $this->version());
    }

    public function test_bandeau_navigateur_integre(): void
    {
        $ua = 'Mozilla/5.0 (Linux; Android 13; SM-A135F Build/TP1A; wv) AppleWebKit/537.36 (KHTML, like Gecko) Version/4.0 Chrome/119.0 Mobile Safari/537.36 [FB_IAB/FB4A;FBAV/440.0.0.0;]';

        $this->withHeader('User-Agent', $ua)->get(route('pacte.show'))
            ->assertOk()
            ->assertSee('id="inapp-banner"', false)
            ->assertSee('intent://boursiv.com/jeux/le-pacte', false);

        $chrome = 'Mozilla/5.0 (Linux; Android 13; SM-A135F) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/119.0 Mobile Safari/537.36';
        $this->withHeader('User-Agent', $chrome)->get(route('pacte.show'))->assertDontSee('id="inapp-banner"', false);
    }

    public function test_connexion_revient_sur_le_jeu(): void
    {
        $this->get(route('pacte.login'))->assertRedirect(route('login'));
        $this->assertSame(route('pacte.show'), session('url.intended'));

        $this->actingAs($this->player)->get(route('pacte.login'))->assertRedirect(route('pacte.show'));
    }

    // ─── Fichiers du jeu ────────────────────────────────────────────────────

    public function test_le_jeu_est_servi_avec_le_pont_et_cache_long(): void
    {
        $response = $this->get($this->iframeSrc());

        $response->assertOk()
            ->assertHeader('X-Frame-Options', 'SAMEORIGIN')
            ->assertHeader('Cache-Control', 'immutable, max-age=31536000, public');
        $this->assertStringStartsWith('text/html', $response->headers->get('Content-Type'));
        // Le jeu tel quel, précédé du pont PacteHost (cf. PacteApiTest)
        $this->assertSame(
            \App\Http\Controllers\PacteController::HOST_BRIDGE . file_get_contents(resource_path('games/le-pacte/index.html')),
            $response->getContent()
        );
    }

    public function test_les_images_sont_servies_par_chemin_relatif(): void
    {
        $base = '/jeux/le-pacte/v/' . $this->version() . '/img/';

        $jpg = $this->get($base . 'hero.jpg');
        $jpg->assertOk()->assertHeader('Content-Type', 'image/jpeg')
            ->assertHeader('Cache-Control', 'immutable, max-age=31536000, public');
        $this->assertSame(filesize(resource_path('games/le-pacte/img/hero.jpg')), $jpg->baseResponse->getFile()->getSize());

        $this->get($base . 'yao.png')->assertOk()->assertHeader('Content-Type', 'image/png');
    }

    public function test_les_gros_plans_ajoutes_sont_servis_par_la_route_versionnee(): void
    {
        $base = '/jeux/le-pacte/v/' . $this->version() . '/img/';

        foreach (['gp_tantie_figee', 'gp_tantie_colere', 'gp_attieke', 'gp_bananes', 'gp_petit', 'gp_yao', 'gp_ama', 'gp_kouassi'] as $nom) {
            $response = $this->get($base . $nom . '.jpg');
            $response->assertOk()
                ->assertHeader('Content-Type', 'image/jpeg')
                ->assertHeader('Cache-Control', 'immutable, max-age=31536000, public');
            $this->assertSame(filesize(resource_path("games/le-pacte/img/{$nom}.jpg")), $response->baseResponse->getFile()->getSize(), $nom);
        }
    }

    public function test_toutes_les_images_du_jeu_existent(): void
    {
        $html = file_get_contents(resource_path('games/le-pacte/index.html'));
        preg_match_all('#img/([A-Za-z0-9_-]+\.(?:jpg|png))#', $html, $m);

        // img/x.jpg, img/xxx_vide.jpg : exemples dans des commentaires du jeu, pas des images
        $missing = array_filter(array_unique($m[1]), fn ($f) => !in_array($f, ['x.jpg', 'xxx_vide.jpg'], true)
            && !is_file(resource_path('games/le-pacte/img/' . $f)));

        $this->assertSame([], array_values($missing));
    }

    public function test_les_fichiers_du_jeu_n_ouvrent_pas_de_session(): void
    {
        $base = '/jeux/le-pacte/v/' . $this->version();

        foreach (['/index.html', '/img/hero.jpg'] as $path) {
            $this->assertSame([], $this->get($base . $path)->headers->getCookies(), $path);
        }
    }

    public function test_une_ancienne_version_est_servie_mais_revalidee(): void
    {
        $this->get('/jeux/le-pacte/v/0123456789abcdef/index.html')
            ->assertOk()->assertHeader('Cache-Control', 'no-cache, private');
        $this->get('/jeux/le-pacte/v/0123456789abcdef/img/hero.jpg')
            ->assertOk()->assertHeader('Cache-Control', 'no-cache, public');
    }

    public function test_aucun_autre_fichier_n_est_accessible(): void
    {
        $v = $this->version();

        $this->get("/jeux/le-pacte/v/{$v}/img/absente.jpg")->assertNotFound();
        $this->get("/jeux/le-pacte/v/{$v}/img/..%2F..%2Fakwaba.html")->assertNotFound();
        $this->get("/jeux/le-pacte/v/{$v}/img/..%2FINTEGRATION.md")->assertNotFound();
        $this->get("/jeux/le-pacte/v/{$v}/INTEGRATION.md")->assertNotFound();
        $this->get("/jeux/le-pacte/v/{$v}/img/hero.svg")->assertNotFound();
        $this->get('/jeux/le-pacte/v/pas-une-version/index.html')->assertNotFound();
    }

    // ─── Catalogue ──────────────────────────────────────────────────────────

    public function test_la_fiche_propose_jouer_gratuitement_au_lieu_d_acheter(): void
    {
        foreach ([null, $this->player] as $viewer) {
            $response = $viewer ? $this->actingAs($viewer)->get(route('marketplace.show', 'le-pacte')) : $this->get(route('marketplace.show', 'le-pacte'));

            $response->assertOk()
                ->assertSee('Jouer gratuitement')
                ->assertSee('Gratuit pour commencer')
                ->assertSee('Histoire complète 1 000 FCFA')
                ->assertSee(route('pacte.show'), false)
                ->assertSee(asset('img/jeux/le-pacte/le-pacte-hero.jpg'), false)
                ->assertDontSee(route('paystack.marketplace.buy', $this->product), false)
                ->assertDontSee('Se connecter pour acheter')
                ->assertDontSee('Payer maintenant');
        }
    }

    public function test_la_carte_du_catalogue_renvoie_vers_le_jeu(): void
    {
        $this->get(route('marketplace.index', ['type' => 'game']))
            ->assertOk()
            ->assertSee('Le Pacte avec les caïmans de Yamoussoukro')
            ->assertSee(route('pacte.show'), false);
    }

    public function test_les_routes_marketplace_standard_ne_servent_jamais_le_jeu(): void
    {
        $this->purchase();
        $this->actingAs($this->player);

        $this->get(route('my.products.play', $this->product))->assertRedirect(route('pacte.show'));
        $this->get(route('my.products.download', $this->product))->assertRedirect(route('pacte.show'));
        $this->get(route('my.products.game-html', $this->product))->assertNotFound();

        $this->product->update(['game_html' => '<html>copie</html>']);
        $this->get(route('my.products.game-html', $this->product))->assertNotFound();

        $this->get(route('my.products'))->assertOk()->assertSee(route('pacte.show'), false);
    }

    public function test_l_achat_depuis_la_fiche_renvoie_vers_le_jeu(): void
    {
        $this->actingAs($this->player)
            ->post(route('paystack.marketplace.buy', $this->product))
            ->assertRedirect(route('pacte.show'));
    }
}
