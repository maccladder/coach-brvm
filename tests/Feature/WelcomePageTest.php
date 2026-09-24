<?php

namespace Tests\Feature;

use App\Models\News;
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

    private function news(string $titre, array $attrs = []): News
    {
        static $i = 0;
        $i++;

        return News::create(array_merge([
            'title'        => $titre,
            'resume'       => "Résumé de : $titre",
            'source_name'  => 'Sika Finance',
            'source_url'   => "https://example.com/article-$i",
            'categorie'    => 'Marché',
            'impact'       => 'Moyen',
            'is_published' => true,
            'published_at' => now(),
        ], $attrs));
    }

    /** Contenu HTML du bloc « À la une » uniquement. */
    private function blocUne(): string
    {
        $html  = $this->get('/welcome')->assertOk()->getContent();
        $debut = strpos($html, 'id="a-la-une"');
        $this->assertNotFalse($debut, 'Bloc « À la une » absent');

        return substr($html, $debut, strpos($html, '</section>', $debut) - $debut);
    }

    public function test_a_la_une_without_news_shows_clean_message(): void
    {
        $bloc = $this->blocUne();

        $this->assertStringContainsString('Les actualités de la BRVM arrivent chaque matin', $bloc);
        $this->assertStringContainsString(route('news.index'), $bloc);
    }

    public function test_a_la_une_with_single_news_shows_it_as_featured_with_source(): void
    {
        $this->news('Bridge Bank démarre fort à la BRVM', ['source_name' => 'Financial Afrik']);

        $bloc = $this->blocUne();

        $this->assertStringContainsString('cb-une-vedette', $bloc);
        $this->assertStringContainsString('Bridge Bank démarre fort à la BRVM', $bloc);
        $this->assertStringContainsString('Financial Afrik', $bloc);
        $this->assertStringNotContainsString('cb-une-item"', $bloc);
        $this->assertStringContainsString('Toutes les actualités', $bloc);
    }

    public function test_featured_is_latest_high_impact_of_last_24h_then_most_recent_others(): void
    {
        $this->travelTo(now()->setTime(10, 0));
        $this->news('Élevé ancien (30 h)', ['impact' => 'Élevé', 'published_at' => now()->subHours(30)]);
        $this->news('Élevé du matin', ['impact' => 'Élevé', 'published_at' => now()->subHours(2)]);
        $this->news('Moyen le plus récent', ['impact' => 'Moyen', 'published_at' => now()->subMinutes(10)]);
        $this->news('Faible récent', ['impact' => 'Faible', 'published_at' => now()->subHour()]);

        $une = News::aLaUne(3);

        $this->assertSame('Élevé du matin', $une['vedette']->title);
        $this->assertSame(
            ['Moyen le plus récent', 'Faible récent', 'Élevé ancien (30 h)'],
            $une['suivants']->pluck('title')->all()
        );

        $bloc = $this->blocUne();
        $this->assertStringContainsString('cb-impact-eleve', $bloc);
        $this->assertLessThan(strpos($bloc, 'Moyen le plus récent'), strpos($bloc, 'Élevé du matin'));
    }

    public function test_featured_falls_back_to_latest_when_no_high_impact_in_24h(): void
    {
        $this->news('Élevé d\'avant-hier', ['impact' => 'Élevé', 'published_at' => now()->subHours(49)]);
        $this->news('Dernier publié', ['impact' => 'Faible', 'published_at' => now()->subMinutes(5)]);

        $this->assertSame('Dernier publié', News::aLaUne()['vedette']->title);
    }

    public function test_unpublished_and_scheduled_news_are_not_shown(): void
    {
        $this->news('Article visible');
        $this->news('Article dépublié', ['is_published' => false]);
        $this->news('Article programmé demain', ['published_at' => now()->addDay()]);

        $bloc = $this->blocUne();

        $this->assertStringContainsString('Article visible', $bloc);
        $this->assertStringNotContainsString('Article dépublié', $bloc);
        $this->assertStringNotContainsString('Article programmé demain', $bloc);
    }

    public function test_a_la_une_comes_right_after_hero(): void
    {
        $this->news('Une actu');

        $html = $this->get('/welcome')->getContent();

        $this->assertLessThan(strpos($html, 'class="cb-stats"'), strpos($html, 'id="a-la-une"'));
        $this->assertGreaterThan(strpos($html, 'class="cb-hero"'), strpos($html, 'id="a-la-une"'));
    }

    public function test_new_badge_only_on_news_under_24h(): void
    {
        $this->news('Article frais', ['published_at' => now()->subHours(3)]);
        $this->news('Article de la veille', ['published_at' => now()->subHours(30)]);

        $bloc = $this->blocUne();

        $this->assertSame(1, substr_count($bloc, 'cb-news-nouveau'));
        $this->assertLessThan(
            strpos($bloc, 'Article frais'),
            strpos($bloc, 'cb-news-nouveau')
        );
    }

    public function test_counter_of_today_articles(): void
    {
        $this->travelTo(now()->setTime(11, 0));

        // 0 article aujourd'hui : pas de compteur
        $this->news('Hier soir', ['published_at' => now()->subDay()->setTime(20, 0)]);
        $this->assertStringNotContainsString('cb-une-compteur"', $this->blocUne());

        $this->news('Ce matin 1', ['published_at' => now()->setTime(8, 0)]);
        $this->assertStringContainsString("1 article aujourd'hui", $this->blocUne());

        $this->news('Ce matin 2', ['published_at' => now()->setTime(8, 1)]);
        $this->news('Ce matin 3', ['published_at' => now()->setTime(8, 2)]);
        $this->news('Non publié', ['published_at' => now()->setTime(8, 3), 'is_published' => false]);
        $this->assertStringContainsString("3 articles aujourd'hui", $this->blocUne());
    }

    public function test_promo_sections_are_kept_but_moved_below_community_sections(): void
    {
        $this->get('/welcome')->assertOk()->assertSeeInOrder([
            'id="a-la-une"',
            'Annonces récentes',
            'Comment ça marche ?',
            'La communauté des',
            "Tu es à l'étranger",
            // Déplacées vers le bas (aucune supprimée)
            route('comparateur.index'),
            'Programme apporteur',
            'Commence <em>gratuitement',
            'Pack Bours',
            'Services Boursiv',
            'Passe à un autre niveau', // appel final
        ], false);
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
