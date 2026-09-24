<?php

namespace Tests\Feature;

use App\Models\News;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class NewsPagesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Http::preventStrayRequests();
        config(['app.name' => 'Coach BRVM']); // APP_NAME inchangé : les titres utilisent app.brand
    }

    private function article(array $attrs = []): News
    {
        return News::create(array_merge([
            'title'        => "Bridge Bank : +7,5 % pour sa première séance à la BRVM",
            'resume'       => "Le titre BBGC a clôturé à 7 255 FCFA, au-dessus du prix de l'OPV.\n\nLes échanges ont été soutenus.",
            'source_name'  => 'Sika Finance',
            'source_url'   => 'https://example.com/bbgc-premiere-seance',
            'categorie'    => 'Introduction en bourse',
            'impact'       => 'Élevé',
            'societes'     => ['BBGC'],
            'is_published' => true,
            'published_at' => '2026-09-24 08:00:00',
        ], $attrs));
    }

    public function test_article_page_has_open_graph_from_article(): void
    {
        $news = $this->article();
        $url  = route('news.show', $news->slug);

        $html = $this->get($url)->assertOk()->getContent();

        $this->assertStringContainsString('<title>Bridge Bank : +7,5 % pour sa première séance à la BRVM — Boursiv</title>', $html);
        $this->assertStringContainsString('<meta property="og:type" content="article">', $html);
        $this->assertStringContainsString('<meta property="og:title" content="Bridge Bank : +7,5 % pour sa première séance à la BRVM">', $html);
        $this->assertStringContainsString('<meta property="og:description" content="Le titre BBGC a clôturé à 7 255 FCFA, au-dessus du prix de l&#039;OPV. Les échanges', $html);
        $this->assertStringContainsString('<meta property="og:url" content="' . $url . '">', $html);
        $this->assertStringContainsString('<link rel="canonical" href="' . $url . '">', $html);
        $this->assertStringContainsString('<meta property="article:published_time" content="2026-09-24T08:00:00', $html);
        $this->assertStringContainsString('<meta property="article:section" content="Introduction en bourse">', $html);
        $this->assertStringContainsString('<meta property="og:image"', $html);
    }

    public function test_article_description_is_single_line_and_limited(): void
    {
        $news = $this->article(['resume' => str_repeat('Analyse détaillée du marché. ', 30)]);

        $html = $this->get(route('news.show', $news->slug))->getContent();
        preg_match('/<meta property="og:description" content="([^"]*)">/', $html, $m);

        $this->assertNotEmpty($m);
        $this->assertLessThanOrEqual(203, mb_strlen(html_entity_decode($m[1])));
        $this->assertStringNotContainsString("\n", $m[1]);
    }

    public function test_news_index_has_title_and_open_graph(): void
    {
        $this->article();

        $html = $this->get(route('news.index'))->assertOk()->getContent();

        $this->assertStringContainsString('<title>Actualités de la BRVM — Boursiv</title>', $html);
        $this->assertStringContainsString('<meta property="og:url" content="' . route('news.index') . '">', $html);
        $this->assertStringContainsString('<meta name="description" content="L&#039;essentiel', $html);
    }
}
