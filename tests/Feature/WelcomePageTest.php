<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class WelcomePageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Http::preventStrayRequests(); // l'accueil ne doit appeler aucun service externe
    }

    public function test_response_starts_with_doctype_without_bom(): void
    {
        $html = $this->get('/welcome')->assertOk()->getContent();

        $this->assertStringStartsWith('<!doctype html>', ltrim($html));
        $this->assertStringNotContainsString("\u{FEFF}", $html);
    }

    public function test_compact_hero_keeps_slogan_and_signup_as_main_cta_for_guests(): void
    {
        $this->get('/welcome')
            ->assertOk()
            ->assertSee('prospérer</span> demain.', false)
            ->assertSeeInOrder(['cb-hero-ctas', route('register'), "S'inscrire gratuitement"], false)
            ->assertSee('col-lg-5 d-none d-lg-block', false); // carte « Outils » masquée sur mobile
    }

    public function test_hero_hides_signup_for_logged_in_users(): void
    {
        $user = \App\Models\User::factory()->create();

        $html = $this->actingAs($user)->get('/welcome')->assertOk()->getContent();
        $hero = substr($html, strpos($html, 'class="cb-hero-ctas'), 800);

        $this->assertStringNotContainsString(route('register'), $hero);
        $this->assertStringContainsString(route('radar.index'), $hero);
    }
}
