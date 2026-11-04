<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Routes de test et de diagnostic (appels externes, IA payante, formulaire de paiement de test) :
 * code admin obligatoire. Elles étaient publiques.
 */
class TestRoutesProtectionTest extends TestCase
{
    use RefreshDatabase;

    private const GET_ROUTES  = ['/test/brvm-actions-ai', '/ssl-http-test', '/ga-test', '/test/paystack', '/summaries/generate'];
    private const POST_ROUTES = ['/test/paystack/start', '/summaries/generate'];

    public function test_les_routes_de_test_refusent_les_visiteurs_et_les_comptes_non_admin(): void
    {
        Http::preventStrayRequests();   // aucun appel externe ne doit partir

        foreach ([null, User::factory()->create()] as $who) {
            if ($who) {
                $this->actingAs($who);
            }
            foreach (self::GET_ROUTES as $url) {
                $this->get($url)->assertRedirect(route('admin.login.form'));
            }
            foreach (self::POST_ROUTES as $url) {
                $this->post($url)->assertRedirect(route('admin.login.form'));
            }
        }
    }

    public function test_les_formulaires_de_test_restent_accessibles_a_l_admin(): void
    {
        $this->withSession(['is_admin' => true])->get('/test/paystack')->assertOk();
        $this->withSession(['is_admin' => true])->get('/summaries/generate')->assertOk();
    }

    public function test_les_pages_publiques_des_resumes_et_le_retour_paystack_restent_publics(): void
    {
        $this->get('/summaries')->assertOk();
        // Paystack y redirige le navigateur : jamais derrière le code admin
        $callback = $this->get('/paystack/callback');
        $this->assertStringNotContainsString('/admin/login', (string) $callback->headers->get('Location'));
    }
}
