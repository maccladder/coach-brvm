<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_screen_can_be_rendered(): void
    {
        $response = $this->get('/register');

        $response->assertStatus(200);
    }

    public function test_new_users_can_register(): void
    {
        Http::fake([
            'hcaptcha.com/*' => Http::response(['success' => true]),
        ]);

        $response = $this->post('/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'h-captcha-response' => 'fake-token',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('dashboard', absolute: false));
    }

    private function register(): \Illuminate\Testing\TestResponse
    {
        Http::fake([
            'hcaptcha.com/*' => Http::response(['success' => true]),
        ]);

        return $this->post('/register', [
            'name' => "N'Guessan Test",
            'email' => 'joueur@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'h-captcha-response' => 'fake-token',
        ]);
    }

    public function test_registration_returns_to_the_intended_page(): void
    {
        // Ex. invité au cadenas d'Akwaba → connexion → « Créer un compte »
        $this->get(route('akwaba.login'))->assertRedirect(route('login'));

        $response = $this->register();

        $this->assertAuthenticated();
        $response->assertRedirect(route('akwaba.show'));
    }

    public function test_registration_without_intended_page_falls_back_to_dashboard(): void
    {
        $response = $this->register();

        $this->assertAuthenticated();
        $response->assertRedirect(route('dashboard', absolute: false));
    }

    public function test_registration_rejects_name_with_url(): void
    {
        Http::fake([
            'hcaptcha.com/*' => Http::response(['success' => true]),
        ]);

        $response = $this->post('/register', [
            'name' => 'http://spam.com',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'h-captcha-response' => 'fake-token',
        ]);

        $response->assertSessionHasErrors('name');
        $this->assertGuest();
    }

    public function test_registration_rejects_disposable_email(): void
    {
        Http::fake([
            'hcaptcha.com/*' => Http::response(['success' => true]),
        ]);

        $response = $this->post('/register', [
            'name' => 'Test User',
            'email' => 'test@mailinator.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'h-captcha-response' => 'fake-token',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_registration_rejects_invalid_captcha(): void
    {
        Http::fake([
            'hcaptcha.com/*' => Http::response(['success' => false]),
        ]);

        $response = $this->post('/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'h-captcha-response' => 'fake-token',
        ]);

        $response->assertSessionHasErrors('h-captcha-response');
        $this->assertGuest();
    }
}
