<?php

namespace Tests\Feature;

use App\Models\News;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class NewsWebhookTest extends TestCase
{
    use RefreshDatabase;

    private const HEADERS = ['X-API-KEY' => 'test-key'];

    protected function setUp(): void
    {
        parent::setUp();
        Http::fake(); // bandeau du layout : pas d'appel à brvm.org
        config(['services.n8n.api_key' => 'test-key']);
    }

    private function article(array $overrides = []): array
    {
        return array_merge([
            'source'    => 'Sika Finance',
            'titre'     => 'Bridge Bank Group CI fait son entrée à la BRVM',
            'url'       => 'https://example.com/bridge-bank-brvm',
            'resume'    => 'Première cotation de BBGC au compartiment principal.',
            'impact'    => 'Élevé',
            'categorie' => 'Introduction en bourse',
            'societes'  => ['BBGC'],
            'mots_cles' => ['IPO', 'banque'],
        ], $overrides);
    }

    public function test_article_is_published_immediately(): void
    {
        $this->freezeSecond();

        $response = $this->postJson('/api/n8n/news', $this->article(), self::HEADERS)
            ->assertCreated()
            ->assertJson(['status' => 'publie']);

        $news = News::findOrFail($response->json('id'));
        $this->assertTrue($news->is_published);
        $this->assertTrue($news->published_at->equalTo(now()));
    }

    public function test_same_article_posted_twice_creates_one_published_article_visible_on_news_page(): void
    {
        $first = $this->postJson('/api/n8n/news', $this->article(), self::HEADERS)->assertCreated();

        $this->postJson('/api/n8n/news', $this->article(['titre' => 'Titre modifié']), self::HEADERS)
            ->assertOk()
            ->assertExactJson(['status' => 'doublon', 'id' => $first->json('id')]);

        $this->assertSame(1, News::count());
        $this->assertTrue(News::first()->is_published);
        $this->assertSame('Bridge Bank Group CI fait son entrée à la BRVM', News::first()->title);

        $this->get(route('news.index'))->assertOk()->assertSee('Bridge Bank Group CI fait son entrée à la BRVM');
        $this->get(route('news.show', News::first()))->assertOk();
    }

    public function test_unpublished_article_stays_unpublished_when_n8n_resends_it(): void
    {
        $draft = News::create([
            'title'        => 'Article dépublié par l\'admin',
            'resume'       => '',
            'source_url'   => 'https://example.com/bridge-bank-brvm',
            'is_published' => false,
        ]);

        $this->postJson('/api/n8n/news', $this->article(), self::HEADERS)
            ->assertOk()
            ->assertExactJson(['status' => 'doublon', 'id' => $draft->id]);

        $draft->refresh();
        $this->assertFalse($draft->is_published);
        $this->assertNull($draft->published_at);
        $this->assertSame(1, News::count());
    }

    public static function champsObligatoires(): array
    {
        return ['sans url' => ['url'], 'sans titre' => ['titre']];
    }

    #[DataProvider('champsObligatoires')]
    public function test_missing_required_field_is_rejected_with_json_422(string $champ): void
    {
        $payload = $this->article();
        unset($payload[$champ]);

        // Envoi « à la n8n » : pas d'en-tête Accept: application/json
        $this->post('/api/n8n/news', $payload, self::HEADERS)
            ->assertStatus(422)
            ->assertHeader('Content-Type', 'application/json')
            ->assertJsonPath('error', 'validation_failed')
            ->assertJsonValidationErrors([$champ]);

        $this->assertSame(0, News::count());
    }

    public function test_concurrent_insert_of_same_url_returns_doublon_not_500(): void
    {
        // Simule une 2e requête qui insère la même URL entre la vérification
        // d'existence et l'INSERT de celle-ci.
        $concurrentId = null;
        News::creating(function () use (&$concurrentId) {
            if ($concurrentId === null) {
                $concurrentId = DB::table('news')->insertGetId([
                    'title' => 'Envoi concurrent', 'slug' => 'envoi-concurrent', 'resume' => '',
                    'source_url' => 'https://example.com/bridge-bank-brvm',
                    'is_published' => true, 'published_at' => now(),
                    'created_at' => now(), 'updated_at' => now(),
                ]);
            }
        });

        $this->postJson('/api/n8n/news', $this->article(), self::HEADERS)
            ->assertOk()
            ->assertExactJson(['status' => 'doublon', 'id' => $concurrentId]);

        $this->assertSame(1, News::count());
    }

    public function test_api_key_is_required(): void
    {
        $this->postJson('/api/n8n/news', $this->article())->assertUnauthorized();
        $this->assertSame(0, News::count());
    }

    public function test_admin_can_still_unpublish_and_delete(): void
    {
        $id = $this->postJson('/api/n8n/news', $this->article(), self::HEADERS)->json('id');
        $news = News::findOrFail($id);

        $news->update(['is_published' => false]);
        $this->get(route('news.show', $news))->assertNotFound();
        $this->get(route('news.index'))->assertDontSee('Bridge Bank Group CI fait son entrée à la BRVM');

        $news->delete();
        $this->assertSame(0, News::count());
    }
}
