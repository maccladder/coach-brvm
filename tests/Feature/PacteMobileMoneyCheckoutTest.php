<?php

namespace Tests\Feature;

use App\Models\GameCredit;
use App\Models\MarketplaceProduct;
use App\Models\Payment;
use App\Models\User;
use App\Services\PacteService;
use App\Services\PaystackService;
use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Promise\Create;
use GuzzleHttp\Psr7\Response;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Psr\Http\Message\RequestInterface;
use Tests\TestCase;

/**
 * Le Pacte dans le checkout Mobile Money (type « pacte ») : mêmes parcours qu'Akwaba,
 * Paystack simulé au niveau HTTP.
 */
class PacteMobileMoneyCheckoutTest extends TestCase
{
    use RefreshDatabase;

    private const SECRET = 'sk_test_webhook_secret';

    private array $calls = [];
    private array $routes = [];
    private MarketplaceProduct $product;
    private User $player;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.paystack.secret'     => self::SECRET,
            'checkout.types.pacte.enabled' => true,
        ]);
        Mail::fake();

        $handler = function (RequestInterface $request) {
            $path = $request->getUri()->getPath();
            $this->calls[] = ['method' => $request->getMethod(), 'path' => $path, 'body' => json_decode((string) $request->getBody(), true) ?? []];

            foreach ($this->routes as $key => [$status, $body]) {
                [$method, $prefix] = explode(' ', $key, 2);
                if ($method === $request->getMethod() && str_starts_with($path, $prefix)) {
                    return Create::promiseFor(new Response($status, ['Content-Type' => 'application/json'], json_encode($body)));
                }
            }

            return Create::promiseFor(new Response(404, [], json_encode(['status' => false, 'message' => 'not mocked'])));
        };

        $client = new Client(['base_uri' => 'https://api.paystack.co', 'handler' => HandlerStack::create($handler)]);
        $this->app->instance(PaystackService::class, new PaystackService($client));

        $this->product = MarketplaceProduct::where('slug', 'le-pacte')->firstOrFail();
        $this->player  = User::factory()->create();
        $this->actingAs($this->player);
    }

    private function paystackReturns(string $key, array $body, int $status = 200): void
    {
        $this->routes[$key] = [$status, $body];
    }

    private function calls(string $method, string $prefix): array
    {
        return array_values(array_filter($this->calls, fn ($c) => $c['method'] === $method && str_starts_with($c['path'], $prefix)));
    }

    private function pay(string $which)
    {
        $this->paystackReturns('POST /charge', ['status' => true, 'message' => 'Charge attempted', 'data' => [
            'status' => 'pay_offline', 'display_text' => 'Please complete authorization on your mobile phone',
        ]]);

        return $this->postJson(route('checkout.mm.pay', 'pacte'), ['product' => $which, 'operator' => 'orange', 'phone' => '0707123456']);
    }

    private function webhook(string $reference, int $amountXof)
    {
        $json = json_encode(['event' => 'charge.success', 'data' => [
            'reference' => $reference, 'status' => 'success', 'amount' => $amountXof * 100, 'currency' => 'XOF', 'channel' => 'mobile_money',
        ]]);

        return $this->call('POST', '/paystack/webhook', [], [], [], [
            'CONTENT_TYPE'              => 'application/json',
            'HTTP_X_PAYSTACK_SIGNATURE' => hash_hmac('sha512', $json, self::SECRET),
        ], $json);
    }

    public function test_buy_renvoie_le_checkout_mobile_money_et_classic_la_page_paystack(): void
    {
        foreach (['full', 'hints5'] as $which) {
            $this->postJson(route('pacte.buy'), ['product' => $which])
                ->assertOk()
                ->assertJsonPath('url', route('checkout.mm.show', ['type' => 'pacte', 'product' => $which]));
        }
        $this->assertCount(0, $this->calls('POST', '/transaction/initialize'));

        $this->paystackReturns('POST /transaction/initialize', ['status' => true, 'data' => ['authorization_url' => 'https://checkout.paystack.com/xyz']]);
        $this->postJson(route('pacte.buy'), ['product' => 'hints5', 'classic' => 1])
            ->assertOk()->assertJsonPath('url', 'https://checkout.paystack.com/xyz');
        $this->assertSame(route('pacte.payment.return'), $this->calls('POST', '/transaction/initialize')[0]['body']['callback_url']);
    }

    public function test_drapeau_false_garde_la_page_paystack_hebergee(): void
    {
        config(['checkout.types.pacte.enabled' => false]);
        $this->paystackReturns('POST /transaction/initialize', ['status' => true, 'data' => ['authorization_url' => 'https://checkout.paystack.com/abc']]);

        $this->postJson(route('pacte.buy'), ['product' => 'full'])->assertOk()->assertJsonPath('url', 'https://checkout.paystack.com/abc');
    }

    public function test_l_ecran_affiche_le_produit_et_le_retour_au_jeu(): void
    {
        $this->get(route('checkout.mm.show', ['type' => 'pacte', 'product' => 'full']))
            ->assertOk()
            ->assertSee('Le Pacte · Histoire complète')
            ->assertSee('Chapitres 3 à 7 + 5 fins')
            ->assertSee(route('pacte.show', ['paiement' => 'annule', 'produit' => 'full']));

        $this->get(route('checkout.mm.show', ['type' => 'pacte', 'product' => 'hints5']))
            ->assertOk()->assertSee('Le Pacte · 5 indices');

        $this->get(route('checkout.mm.show', ['type' => 'pacte', 'product' => 'vip']))->assertRedirect(route('pacte.show'));
    }

    public function test_full_valide_par_le_webhook(): void
    {
        $reference = $this->pay('full')->assertOk()->json('reference');
        $this->assertMatchesRegularExpression('/^MP-\d{14}-[A-Z0-9]{6}$/', $reference);

        $charge = $this->calls('POST', '/charge')[0]['body'];
        $this->assertSame(100000, $charge['amount']);
        $this->assertSame(['le-pacte', 'full', $this->product->id], [$charge['metadata']['game'], $charge['metadata']['product'], $charge['metadata']['product_id']]);
        $this->assertSame('mobile_money_checkout', Payment::where('transaction_id', $reference)->value('meta')['channel']);

        $this->webhook($reference, 1000)->assertOk();

        $this->getJson(route('checkout.mm.status', $reference))
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('success_url', route('pacte.show', ['paid' => 1]));
        $this->assertTrue(app(PacteService::class)->isUnlocked($this->player->fresh()));
    }

    public function test_hints5_valide_en_relisant_paystack(): void
    {
        $reference = $this->pay('hints5')->assertOk()->json('reference');
        $this->assertStringStartsWith('PCT-H-' . $this->player->id . '-', $reference);
        $this->assertSame(50000, $this->calls('POST', '/charge')[0]['body']['amount']);

        $this->paystackReturns('GET /charge/', ['status' => true, 'data' => ['reference' => $reference, 'status' => 'success', 'amount' => 50000, 'currency' => 'XOF']]);
        $this->paystackReturns('GET /transaction/verify/', ['status' => true, 'data' => [
            'reference' => $reference, 'status' => 'success', 'amount' => 50000, 'currency' => 'XOF',
            'metadata'  => ['user_id' => $this->player->id, 'game' => 'le-pacte', 'product' => 'hints5'],
        ]]);
        $this->travel(12)->seconds();

        $this->getJson(route('checkout.mm.status', $reference))
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('success_url', route('pacte.show', ['paid' => 1]));

        $this->assertSame(5, (int) GameCredit::where('user_id', $this->player->id)->where('game_slug', 'le-pacte')->value('balance'));
    }

    public function test_histoire_deja_debloquee_renvoie_au_jeu(): void
    {
        DB::table('marketplace_purchases')->insert([
            'user_id' => $this->player->id, 'product_id' => $this->product->id, 'amount' => 1000,
            'provider' => 'paystack', 'status' => 'paid', 'paid_at' => now(), 'created_at' => now(), 'updated_at' => now(),
        ]);

        $this->get(route('checkout.mm.show', ['type' => 'pacte', 'product' => 'full']))->assertRedirect(route('pacte.show'));
    }
}
