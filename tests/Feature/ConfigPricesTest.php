<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Prix et secret lus via config() (et non env(), null sous config:cache).
 */
class ConfigPricesTest extends TestCase
{
    use RefreshDatabase;

    public function test_le_prix_de_l_analyse_financiere_vient_de_la_config(): void
    {
        $this->assertSame(1000, config('services.paystack.price_financial'));   // valeur par défaut

        config(['services.paystack.price_financial' => 2500]);

        $this->actingAs(User::factory()->create())
            ->get(route('client-financials.create'))
            ->assertOk()
            ->assertSee('Montant : 2500 FCFA');
    }

    public function test_le_webhook_paystack_refuse_sans_secret_configure(): void
    {
        // Plus de repli sur env('PAYSTACK_SECRET_KEY') : seule la config fait foi
        config(['services.paystack.secret' => null]);

        $this->call('POST', '/paystack/webhook', [], [], [], [
            'CONTENT_TYPE' => 'application/json', 'HTTP_X_PAYSTACK_SIGNATURE' => 'x',
        ], '{}')->assertStatus(400);
    }
}
