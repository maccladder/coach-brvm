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
