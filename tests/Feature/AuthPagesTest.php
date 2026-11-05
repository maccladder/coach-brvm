<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Pages d'inscription et de connexion (layout invité) : accroche, Google en premier,
 * champs accessibles, erreurs sous chaque champ, logique inchangée.
 */
class AuthPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_inscription_accroche_google_en_premier_et_champs(): void
    {
        $html = $this->get(route('register'))->assertOk()->getContent();

        // Accroche
        $this->assertStringContainsString('Comprends · Analyse · Progresse', $html);
        $this->assertStringContainsString('Joue à Akwaba et sauvegarde ta progression', $html);
        $this->assertStringContainsString('Portefeuille virtuel BRVM', $html);
        $this->assertStringContainsString('Analyses et formations', $html);
        $this->assertStringContainsString('Paiement Mobile Money sécurisé', $html);
        $this->assertStringNotContainsString('bg-white shadow-sm', $html);   // plus de carte blanche

        // Google avant le formulaire, même route OAuth
        $this->assertLessThan(strpos($html, 'action="' . route('register') . '"'), strpos($html, 'href="' . route('auth.google') . '"'));

        // Mêmes champs, CSRF, hCaptcha
        foreach (['name="name"', 'name="email"', 'name="password"', 'name="password_confirmation"', 'name="_token"', 'class="h-captcha"', 'js.hcaptcha.com/1/api.js'] as $needle) {
            $this->assertStringContainsString($needle, $html);
        }
        $this->assertStringContainsString('autocomplete="name"', $html);
        $this->assertMatchesRegularExpression('/name="email" type="email" inputmode="email"/', $html);
        $this->assertStringContainsString('autocomplete="email"', $html);
        $this->assertSame(2, substr_count($html, 'autocomplete="new-password"'));
        $this->assertSame(2, substr_count($html, 'data-toggle-password='));
        $this->assertStringContainsString('data-loading-form', $html);
        $this->assertStringContainsString('<title>Créer un compte · Boursiv</title>', $html);
    }

    public function test_connexion_champs_et_toggle(): void
    {
        $html = $this->get(route('login'))->assertOk()->getContent();

        $this->assertLessThan(strpos($html, 'action="' . route('login') . '"'), strpos($html, 'href="' . route('auth.google') . '"'));
        $this->assertStringContainsString('autocomplete="email"', $html);
        $this->assertStringContainsString('inputmode="email"', $html);
        $this->assertSame(1, substr_count($html, 'autocomplete="current-password"'));
        $this->assertSame(1, substr_count($html, 'data-toggle-password="password"'));
        $this->assertStringContainsString('name="remember"', $html);
        $this->assertStringContainsString(route('password.request'), $html);
    }

    public function test_erreurs_d_inscription_sous_chaque_champ_avec_old(): void
    {
        Http::fake(['hcaptcha.com/*' => Http::response(['success' => false])]);

        $this->from(route('register'))->post(route('register'), [
            'name' => "Aya N'Guessan", 'email' => 'pas-un-email', 'password' => 'court', 'password_confirmation' => 'autre',
            'h-captcha-response' => 'x',
        ])->assertRedirect(route('register'));

        $html = $this->get(route('register'))->getContent();

        $this->assertStringContainsString('value="Aya N&#039;Guessan"', $html);   // old() conservé
        $this->assertStringContainsString('value="pas-un-email"', $html);
        $this->assertMatchesRegularExpression('/class="ba-input\s+is-invalid\s*"\s+value="pas-un-email"/', $html);
        $this->assertStringContainsString('aria-describedby="email-error"', $html);
        $this->assertMatchesRegularExpression('/<p class="ba-error" id="email-error">[^<]+<\/p>/', $html);
        $this->assertMatchesRegularExpression('/<p class="ba-error" id="password-error">[^<]+<\/p>/', $html);
        $this->assertStringContainsString('id="captcha-error"', $html);
        $this->assertStringNotContainsString('id="name-error"', $html);
    }

    public function test_erreur_de_connexion_sous_l_email(): void
    {
        User::factory()->create(['email' => 'aya@example.com']);

        $this->from(route('login'))->post(route('login'), ['email' => 'aya@example.com', 'password' => 'mauvais'])
            ->assertRedirect(route('login'));

        $html = $this->get(route('login'))->getContent();

        $this->assertStringContainsString('value="aya@example.com"', $html);
        $this->assertMatchesRegularExpression('/<p class="ba-error" id="email-error">[^<]+<\/p>/', $html);
        $this->assertStringContainsString('aria-invalid="true"', $html);
    }

    public function test_l_inscription_ramene_toujours_a_la_page_d_origine(): void
    {
        Http::fake(['hcaptcha.com/*' => Http::response(['success' => true])]);

        $this->get(route('akwaba.login'));   // mémorise /jeux/akwaba comme page d'origine

        $this->post(route('register'), [
            'name' => "Koffi N'Dri", 'email' => 'koffi@example.com',
            'password' => 'motdepasse-solide', 'password_confirmation' => 'motdepasse-solide',
            'h-captcha-response' => 'x',
        ])->assertRedirect(route('akwaba.show'));
    }
}
