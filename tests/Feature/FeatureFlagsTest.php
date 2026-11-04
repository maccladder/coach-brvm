<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Document;
use App\Models\MarketplaceProduct;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Modules masquables (config/features.php) : analyse de BOC, états financiers, CinetPay.
 * Coupés : liens absents, routes publiques en 404, admin en lecture seule. Rien n'est supprimé.
 */
class FeatureFlagsTest extends TestCase
{
    use RefreshDatabase;

    private const FLAGS = ['boc_analysis', 'financial_statements', 'cinetpay'];

    protected function setUp(): void
    {
        parent::setUp();
        Http::preventStrayRequests();   // aucun appel CinetPay (ni autre) pendant les tests
    }

    private function flags(bool $on): void
    {
        foreach (self::FLAGS as $flag) {
            config(['features.' . $flag => $on]);
        }
    }

    private function boc(): int
    {
        return DB::table('client_bocs')->insertGetId([
            'title' => 'BOC test', 'boc_date' => now()->subDay()->toDateString(), 'file_path' => 'bocs/test.pdf',
            'status' => 'done', 'interpreted_markdown' => '## Résumé', 'is_public' => 1,
            'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    private function financial(?User $user = null): int
    {
        return DB::table('client_financials')->insertGetId([
            'title' => 'Bilan test', 'company' => 'SONATEL', 'status' => 'done', 'interpreted_markdown' => '## Analyse',
            'transaction_id' => 'tx-' . uniqid(), 'user_id' => $user?->id, 'amount' => 1000,
            'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    // ─── Configuration ──────────────────────────────────────────────────────

    public function test_les_trois_interrupteurs_existent_et_sont_coupes_par_defaut(): void
    {
        $config = require config_path('features.php');

        $this->assertSame(self::FLAGS, array_keys($config));
        // Sans variable dans .env, chaque module est coupé
        foreach (['FEATURE_BOC_ANALYSIS', 'FEATURE_FINANCIAL_STATEMENTS', 'FEATURE_CINETPAY'] as $var) {
            $this->assertStringContainsString("env('{$var}', false)", file_get_contents(config_path('features.php')));
        }
    }

    // ─── Liens ──────────────────────────────────────────────────────────────

    public function test_coupes_aucun_lien_dans_le_menu_l_accueil_le_tableau_de_bord_et_la_faq(): void
    {
        $this->flags(false);
        $this->boc();   // même avec une BOC publique en base, aucun lien « BOC gratuite »
        $user = User::factory()->create();

        $pages = [
            $this->get('/welcome')->assertOk()->getContent(),
            $this->get(route('marketplace.index'))->assertOk()->getContent(),
            $this->get(route('faq'))->assertOk()->getContent(),
            $this->actingAs($user)->get(route('dashboard'))->assertOk()->getContent(),
        ];

        foreach ($pages as $html) {
            foreach (['/client-bocs', '/client-financials', 'Analyser une BOC', 'Analyser un état financier', 'BOC gratuite', 'Mes analyses'] as $needle) {
                $this->assertStringNotContainsString($needle, $html, $needle);
            }
        }
        $this->assertStringNotContainsString('Comment Boursiv analyse les BOC', $pages[2]);
        $this->assertStringContainsString("C'est quoi un BOC", html_entity_decode($pages[2], ENT_QUOTES));   // pédagogie conservée
    }

    public function test_actives_les_liens_reviennent(): void
    {
        $this->flags(true);
        $this->boc();
        $user = User::factory()->create();

        $welcome = $this->get('/welcome')->assertOk()->getContent();
        $this->assertStringContainsString(route('client-bocs.latest.public'), $welcome);
        $this->assertStringContainsString(route('client-financials.create'), $welcome);

        $menu = $this->actingAs($user)->get(route('marketplace.index'))->assertOk()->getContent();
        $this->assertStringContainsString('Analyser une BOC', $menu);
        $this->assertStringContainsString('Analyser un état financier', $menu);
        $this->assertStringContainsString('Mes analyses', $menu);

        $this->assertStringContainsString('Comment Boursiv analyse les BOC', $this->get(route('faq'))->getContent());
    }

    public function test_un_seul_module_coupe_seuls_ses_liens_disparaissent(): void
    {
        config(['features.boc_analysis' => false, 'features.financial_statements' => true, 'features.cinetpay' => false]);

        $html = $this->actingAs(User::factory()->create())->get(route('marketplace.index'))->getContent();

        $this->assertStringNotContainsString('Analyser une BOC', $html);
        $this->assertStringContainsString('Analyser un état financier', $html);
    }

    // ─── Routes publiques ───────────────────────────────────────────────────

    public function test_coupes_les_routes_publiques_renvoient_404(): void
    {
        $this->flags(false);
        $boc = $this->boc();
        $fin = $this->financial();
        $this->actingAs(User::factory()->create());

        foreach (['/client-bocs', '/client-bocs/create', '/client-bocs/latest/public', "/client-bocs/{$boc}", "/client-bocs/{$boc}/status",
                  '/client-financials', '/client-financials/create', "/client-financials/{$fin}", '/client-financials/payment/callback'] as $url) {
            $this->get($url)->assertNotFound();
        }
        $this->post('/client-bocs')->assertNotFound();
        $this->post('/client-financials')->assertNotFound();
        $this->post("/{$boc}/pdf")->assertNotFound();
    }

    public function test_actives_les_routes_repondent(): void
    {
        $this->flags(true);
        $this->actingAs(User::factory()->create());

        $this->get('/client-bocs/create')->assertOk();
        $this->get('/client-financials/create')->assertOk();
    }

    // ─── Admin : lecture seule ──────────────────────────────────────────────

    public function test_coupes_l_admin_consulte_les_donnees_existantes_sans_pouvoir_en_creer(): void
    {
        $this->flags(false);
        $boc = $this->boc();
        $fin = $this->financial();
        $admin = $this->withSession(['is_admin' => true]);

        // Lecture : historique et détail accessibles
        $admin->get('/client-bocs')->assertOk();
        $admin->get("/client-bocs/{$boc}")->assertOk();
        $admin->get("/client-bocs/{$boc}/status")->assertOk();
        $admin->get("/client-financials/{$fin}")->assertOk();
        $admin->get("/client-financials/{$fin}/status")->assertOk();
        $admin->get(route('admin.dashboard'))->assertOk();

        // Création : impossible, même pour l'admin
        $admin->get('/client-bocs/create')->assertNotFound();
        $admin->get('/client-financials/create')->assertNotFound();
        $admin->post('/client-bocs')->assertNotFound();
        $admin->post('/client-financials')->assertNotFound();
        $admin->get('/client-bocs/latest/public')->assertNotFound();
    }

    // ─── CinetPay ───────────────────────────────────────────────────────────

    public function test_cinetpay_coupe_achats_et_retours_en_404_sans_aucun_appel(): void
    {
        $this->flags(false);
        $user    = User::factory()->create();
        $course  = Course::create(['title' => 'Cours', 'slug' => 'cours', 'price' => 5000, 'is_published' => true, 'cf_uid' => 'cf-test']);
        $product = MarketplaceProduct::create(['title' => 'Livre', 'slug' => 'livre', 'type' => 'book', 'status' => 'published', 'price' => 2000]);
        $doc     = Document::create(['title' => 'Étude', 'slug' => 'etude', 'type' => 'etude', 'price' => 3000, 'file_path' => 'docs/e.pdf', 'is_active' => true, 'status' => 'published']);
        $this->actingAs($user);

        $this->post(route('courses.buy', $course))->assertNotFound();
        $this->post(route('marketplace.buy', $product))->assertNotFound();
        $this->post(route('documents.buy', $doc))->assertNotFound();

        foreach (['/payment/cinetpay/return', '/payments/cinetpay/return', '/marketplace/payment/cinetpay/return', '/documents/payment/cinetpay/return'] as $url) {
            $this->get($url)->assertNotFound();
        }
        foreach (['/paiement/cinetpay/ipn', '/payments/cinetpay/ipn', '/marketplace/payment/cinetpay/notify', '/documents/payment/cinetpay/notify', '/client-bocs/payment/notify'] as $url) {
            $this->post($url)->assertNotFound();
        }

        // Les achats Paystack, eux, restent ouverts
        $this->assertNotSame(404, $this->post(route('courses.buy.paystack', $course))->status());
    }

    public function test_le_document_s_achete_par_paystack_avec_whatsapp_en_secours(): void
    {
        $doc = Document::create(['title' => 'Étude marché', 'slug' => 'etude-marche', 'type' => 'etude', 'price' => 3000, 'file_path' => 'docs/e.pdf', 'is_active' => true, 'status' => 'published']);
        $this->actingAs(User::factory()->create());

        // Avec ou sans CinetPay : bouton Paystack + WhatsApp, plus jamais le formulaire CinetPay
        foreach ([false, true] as $cinetpay) {
            config(['features.cinetpay' => $cinetpay]);
            $html = $this->get(route('docs.public.show', $doc->slug))->assertOk()->getContent();
            $this->assertStringNotContainsString('"' . route('documents.buy', $doc) . '"', $html);
            $this->assertStringContainsString(route('paystack.documents.buy', $doc), $html);
            $this->assertStringContainsString('Acheter via WhatsApp', $html);
            $this->assertStringContainsString('https://wa.me/' . config('checkout.whatsapp'), $html);
        }
    }

    // ─── Accueil ────────────────────────────────────────────────────────────

    public function test_l_accueil_s_affiche_sans_erreur_dans_les_deux_etats(): void
    {
        $this->boc();

        foreach ([false, true] as $on) {
            $this->flags($on);
            $this->get('/welcome')->assertOk();
            $this->actingAs(User::factory()->create())->get('/welcome')->assertOk();
            auth()->logout();
        }
    }
}
