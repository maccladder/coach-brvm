<?php

namespace Tests\Feature;

use App\Mail\PaymentReceivedMail;
use App\Models\Document;
use App\Models\DocumentPurchase;
use App\Models\Payment;
use App\Models\User;
use App\Services\PaystackService;
use App\Services\Payments\PaystackDocumentService;
use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Promise\Create;
use GuzzleHttp\Psr7\Response;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Psr\Http\Message\RequestInterface;
use Tests\TestCase;

/**
 * Achat des documents « Études & Business plans » via Paystack (référence DOC-PS-) :
 * montant fixé par le serveur, webhook signé, validation idempotente, checkout Mobile Money
 * derrière CHECKOUT_MM_DOCUMENT, WhatsApp gardé en secours.
 */
class DocumentPaystackPurchaseTest extends TestCase
{
    use RefreshDatabase;

    private const SECRET = 'sk_test_documents';

    private array $calls = [];
    private array $routes = [];
    private Document $doc;
    private User $buyer;

    protected function setUp(): void
    {
        parent::setUp();

        config(['services.paystack.secret' => self::SECRET, 'checkout.types.document.enabled' => false]);
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
        $this->app->instance(PaystackService::class, new PaystackService(new Client(['base_uri' => 'https://api.paystack.co', 'handler' => HandlerStack::create($handler)])));

        $this->doc = Document::create([
            'title' => 'Business plan boulangerie', 'slug' => 'bp-boulangerie', 'type' => 'business_plan',
            'price' => 7500, 'file_path' => 'docs/bp.pdf', 'is_active' => true, 'status' => 'published',
        ]);
        $this->buyer = User::factory()->create(['email' => 'acheteur@example.com']);
    }

    // ─── Helpers ────────────────────────────────────────────────────────────

    private function paystackReturns(string $key, array $body, int $status = 200): void
    {
        $this->routes[$key] = [$status, $body];
    }

    private function calls(string $method, string $prefix): array
    {
        return array_values(array_filter($this->calls, fn ($c) => $c['method'] === $method && str_starts_with($c['path'], $prefix)));
    }

    private function startPurchase(array $input = []): string
    {
        $this->paystackReturns('POST /transaction/initialize', ['status' => true, 'data' => ['authorization_url' => 'https://checkout.paystack.com/doc']]);

        $this->actingAs($this->buyer)
            ->post(route('paystack.documents.buy', $this->doc), $input)
            ->assertRedirect('https://checkout.paystack.com/doc');

        return Payment::where('purpose', 'document')->latest('id')->value('transaction_id');
    }

    private function webhook(string $reference, int $amountXof, string $currency = 'XOF', ?string $signature = null)
    {
        $json = json_encode(['event' => 'charge.success', 'data' => [
            'reference' => $reference, 'status' => 'success', 'amount' => $amountXof * 100,
            'currency' => $currency, 'channel' => 'mobile_money',
        ]]);

        return $this->call('POST', '/paystack/webhook', [], [], [], [
            'CONTENT_TYPE'              => 'application/json',
            'HTTP_X_PAYSTACK_SIGNATURE' => $signature ?? hash_hmac('sha512', $json, self::SECRET),
        ], $json);
    }

    private function bought(): bool
    {
        return $this->doc->fresh()->isBoughtBy($this->buyer);
    }

    // ─── Page Paystack hébergée ─────────────────────────────────────────────

    public function test_l_achat_cree_les_lignes_en_attente_avec_le_prix_du_serveur(): void
    {
        // Un montant envoyé par le navigateur est ignoré
        $reference = $this->startPurchase(['amount' => 100, 'price' => 100]);

        $this->assertStringStartsWith('DOC-PS-', $reference);

        $init = $this->calls('POST', '/transaction/initialize')[0]['body'];
        $this->assertSame(750000, $init['amount']);   // 7 500 XOF × 100
        $this->assertSame('XOF', $init['currency']);
        $this->assertSame($reference, $init['reference']);
        $this->assertSame('acheteur@example.com', $init['email']);
        $this->assertSame(route('paystack.documents.callback'), $init['callback_url']);
        $this->assertSame('document', $init['metadata']['purpose']);

        $payment = Payment::where('transaction_id', $reference)->first();
        $this->assertSame(7500, (int) $payment->amount_paid);
        $this->assertSame('pending', $payment->status);
        $this->assertSame($this->doc->id, data_get($payment->meta, 'document_id'));

        $purchase = DocumentPurchase::where('provider_ref', $reference)->first();
        $this->assertSame('pending', $purchase->status);
        $this->assertSame('paystack', $purchase->provider);
        $this->assertSame(7500, (int) $purchase->amount);
        $this->assertFalse($this->bought());
    }

    public function test_achat_reserve_aux_comptes_connectes(): void
    {
        $this->post(route('paystack.documents.buy', $this->doc))->assertRedirect(route('login'));
        $this->assertSame(0, Payment::count());
    }

    public function test_document_non_publie_ou_deja_achete_ne_lance_aucun_paiement(): void
    {
        $this->doc->update(['status' => 'pending']);
        $this->actingAs($this->buyer)->post(route('paystack.documents.buy', $this->doc))->assertNotFound();

        $this->doc->update(['status' => 'published']);
        DocumentPurchase::create(['user_id' => $this->buyer->id, 'document_id' => $this->doc->id, 'amount' => 7500, 'currency' => 'XOF', 'provider' => 'paystack', 'provider_ref' => 'OLD-1', 'status' => 'paid', 'paid_at' => now()]);

        $this->actingAs($this->buyer)->post(route('paystack.documents.buy', $this->doc))
            ->assertRedirect(route('docs.public.show', $this->doc->slug));

        $this->assertSame(0, Payment::count());
        $this->assertCount(0, $this->calls('POST', '/transaction/initialize'));
    }

    public function test_echec_d_initialisation_ferme_les_lignes(): void
    {
        $this->paystackReturns('POST /transaction/initialize', ['status' => false, 'message' => 'boom'], 400);

        $this->actingAs($this->buyer)->from(route('docs.public.show', $this->doc->slug))
            ->post(route('paystack.documents.buy', $this->doc))
            ->assertRedirect(route('docs.public.show', $this->doc->slug))
            ->assertSessionHas('error');

        $this->assertSame('failed', Payment::where('purpose', 'document')->value('status'));
        $this->assertSame('failed', DocumentPurchase::value('status'));
    }

    // ─── Webhook ────────────────────────────────────────────────────────────

    public function test_webhook_signe_debloque_le_document_une_seule_fois(): void
    {
        $reference = $this->startPurchase();

        $this->webhook($reference, 7500)->assertOk();
        $this->webhook($reference, 7500)->assertOk();   // rejoué par Paystack

        $this->assertTrue($this->bought());
        $this->assertSame(1, DocumentPurchase::paid()->count());
        $payment = Payment::where('transaction_id', $reference)->first();
        $this->assertSame('paid', $payment->status);
        $this->assertNotNull($payment->credited_at);
        Mail::assertSent(PaymentReceivedMail::class, 1);

        // Le téléchargement suit isBoughtBy
        $this->actingAs($this->buyer)->get(route('documents.mine'))->assertOk()->assertSee('Business plan boulangerie');
    }

    public function test_webhook_mal_signe_ne_debloque_rien(): void
    {
        $reference = $this->startPurchase();

        $this->webhook($reference, 7500, 'XOF', 'signature-bidon')->assertStatus(401);

        $this->assertFalse($this->bought());
        $this->assertSame('pending', Payment::where('transaction_id', $reference)->value('status'));
    }

    public function test_montant_ou_devise_incorrects_ne_debloquent_rien(): void
    {
        $reference = $this->startPurchase();

        $this->webhook($reference, 100)->assertOk();
        $this->webhook($reference, 7500, 'NGN')->assertOk();

        $this->assertFalse($this->bought());
        $this->assertNull(Payment::where('transaction_id', $reference)->value('credited_at'));
        Mail::assertNothingSent();
    }

    public function test_deux_tentatives_payees_la_seconde_est_marquee_a_rembourser(): void
    {
        $first  = $this->startPurchase();
        $second = $this->startPurchase();

        $this->webhook($first, 7500)->assertOk();
        $this->assertSame(PaystackDocumentService::DUPLICATE, app(PaystackDocumentService::class)->handleChargeSuccess([
            'reference' => $second, 'amount' => 750000, 'currency' => 'XOF', 'status' => 'success',
        ]));

        $this->assertSame(1, DocumentPurchase::paid()->count());
        $this->assertSame('duplicate', DocumentPurchase::where('provider_ref', $second)->value('status'));
        $meta = Payment::where('transaction_id', $second)->value('meta');
        $this->assertTrue($meta['duplicate']);
        $this->assertSame('to_refund', $meta['refund_status']);
    }

    public function test_un_paiement_d_un_autre_type_n_est_pas_valide_comme_document(): void
    {
        Payment::create(['user_id' => $this->buyer->id, 'transaction_id' => 'DOC-PS-FAKE', 'amount_paid' => 7500, 'amount_virtual' => 0, 'purpose' => 'marketplace', 'status' => 'pending', 'meta' => ['document_id' => $this->doc->id]]);

        $this->assertSame(PaystackDocumentService::IGNORED, app(PaystackDocumentService::class)->handleChargeSuccess([
            'reference' => 'DOC-PS-FAKE', 'amount' => 750000, 'currency' => 'XOF',
        ]));
        $this->assertFalse($this->bought());
    }

    // ─── Retour navigateur ──────────────────────────────────────────────────

    public function test_callback_reverifie_chez_paystack_puis_debloque(): void
    {
        $reference = $this->startPurchase();
        $this->paystackReturns('GET /transaction/verify/', ['status' => true, 'data' => [
            'reference' => $reference, 'status' => 'success', 'amount' => 750000, 'currency' => 'XOF',
        ]]);

        $this->get(route('paystack.documents.callback', ['reference' => $reference]))
            ->assertRedirect(route('docs.public.show', $this->doc->slug))
            ->assertSessionHas('success');

        $this->assertTrue($this->bought());
        $this->assertCount(1, $this->calls('GET', '/transaction/verify/' . $reference));

        // Le message s'affiche sur la page du document, avec le bouton Télécharger
        $this->get(route('docs.public.show', $this->doc->slug))->assertSee('Télécharger');
    }

    public function test_callback_avec_transaction_non_reussie_ne_debloque_rien(): void
    {
        $reference = $this->startPurchase();
        $this->paystackReturns('GET /transaction/verify/', ['status' => true, 'data' => [
            'reference' => $reference, 'status' => 'abandoned', 'amount' => 750000, 'currency' => 'XOF',
        ]]);

        $this->get(route('paystack.documents.callback', ['reference' => $reference]))
            ->assertRedirect(route('docs.public.show', $this->doc->slug))
            ->assertSessionHas('error');

        $this->assertFalse($this->bought());
    }

    public function test_callback_sans_reference_document_ne_contacte_pas_paystack(): void
    {
        $this->get(route('paystack.documents.callback', ['reference' => 'MP-123']))->assertRedirect(route('docs.public.index'));
        $this->assertCount(0, $this->calls('GET', '/transaction/verify/'));
    }

    // ─── Checkout Mobile Money ──────────────────────────────────────────────

    public function test_drapeau_mobile_money_redirige_vers_le_checkout_et_classic_garde_la_page_paystack(): void
    {
        config(['checkout.types.document.enabled' => true]);

        $this->actingAs($this->buyer)->post(route('paystack.documents.buy', $this->doc))
            ->assertRedirect(route('checkout.mm.show', ['type' => 'document', 'document' => $this->doc->id]));
        $this->assertSame(0, Payment::count());

        $this->startPurchase(['classic' => 1]);
        $this->assertSame(1, Payment::count());
    }

    public function test_paiement_mobile_money_puis_webhook(): void
    {
        config(['checkout.types.document.enabled' => true]);
        $this->paystackReturns('POST /charge', ['status' => true, 'message' => 'Charge attempted', 'data' => [
            'status' => 'pay_offline', 'display_text' => 'Please complete authorization on your mobile phone',
        ]]);

        $this->actingAs($this->buyer)->get(route('checkout.mm.show', ['type' => 'document', 'document' => $this->doc->id]))
            ->assertOk()->assertSee('Business plan boulangerie')->assertSee('7 500');

        // Un montant envoyé par le navigateur est ignoré ici aussi
        $reference = $this->postJson(route('checkout.mm.pay', 'document'), [
            'document' => $this->doc->id, 'operator' => 'orange', 'phone' => '0707123456', 'amount' => 1,
        ])->assertOk()->assertJsonPath('status', 'pending')->json('reference');

        $this->assertStringStartsWith('DOC-PS-', $reference);
        $charge = $this->calls('POST', '/charge')[0]['body'];
        $this->assertSame(750000, $charge['amount']);
        $this->assertSame('XOF', $charge['currency']);
        $this->assertSame($this->doc->id, $charge['metadata']['document_id']);
        $this->assertSame('mobile_money_checkout', data_get(Payment::where('transaction_id', $reference)->value('meta'), 'channel'));

        $this->webhook($reference, 7500)->assertOk();

        $this->assertTrue($this->bought());
        $this->getJson(route('checkout.mm.status', $reference))
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('success_label', 'Télécharger le document');
    }

    public function test_checkout_mobile_money_coupe_par_defaut(): void
    {
        $this->assertFalse((bool) config('checkout.types.document.enabled'));
        $this->actingAs($this->buyer)->get(route('checkout.mm.show', ['type' => 'document', 'document' => $this->doc->id]))
            ->assertStatus(404);
    }

    // ─── Réconciliation ─────────────────────────────────────────────────────

    public function test_la_reconciliation_valide_un_document_paye_sans_webhook(): void
    {
        $reference = $this->startPurchase();
        $this->paystackReturns('GET /transaction/verify/', ['status' => true, 'data' => [
            'reference' => $reference, 'status' => 'success', 'amount' => 750000, 'currency' => 'XOF',
        ]]);

        $this->travel(31)->minutes();   // la commande ignore les paiements de moins de 30 min
        $this->artisan('payments:reconcile-paystack')->assertSuccessful();

        $this->assertTrue($this->bought());
        // Une seule fois dans la liste (payments), pas une seconde via document_purchases
        $this->assertCount(1, $this->calls('GET', '/transaction/verify/' . $reference));
    }
}
