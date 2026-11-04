<?php

namespace Tests\Feature;

use App\Services\GoogleAnalyticsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Mockery;
use RuntimeException;
use Tests\TestCase;

/**
 * Admin Analytics (GA4 Data API) : courbe sur 7 jours complets, triée par vraie date,
 * erreurs API journalisées, propriété lue via config().
 */
class AdminAnalyticsTest extends TestCase
{
    use RefreshDatabase;

    public function test_les_jours_sans_donnees_sont_completes_a_zero(): void
    {
        // Seuls les 29 et 30/10 ont des visiteurs (GA4 ne renvoie rien pour les autres jours)
        $days = GoogleAnalyticsService::fillDays(['20261029' => 900, '20261030' => 880], Carbon::parse('2026-11-04'));

        $this->assertSame([
            ['date' => '29/10', 'users' => 900],
            ['date' => '30/10', 'users' => 880],
            ['date' => '31/10', 'users' => 0],
            ['date' => '01/11', 'users' => 0],
            ['date' => '02/11', 'users' => 0],
            ['date' => '03/11', 'users' => 0],
            ['date' => '04/11', 'users' => 0],
        ], $days);
    }

    public function test_le_tri_suit_la_vraie_date_meme_sur_deux_mois(): void
    {
        // Ordre reçu quelconque ; l'ancien tri texte « JJ/MM » mettait 01/11 avant 29/10
        $days = GoogleAnalyticsService::fillDays(['20261101' => 5, '20261029' => 3, '20261102' => 7], Carbon::parse('2026-11-02'));

        $this->assertSame(['27/10', '28/10', '29/10', '30/10', '31/10', '01/11', '02/11'], array_column($days, 'date'));
        $this->assertSame([0, 0, 3, 0, 0, 5, 7], array_column($days, 'users'));
    }

    public function test_les_erreurs_api_sont_journalisees(): void
    {
        Log::spy();

        $service = (new \ReflectionClass(GoogleAnalyticsService::class))->newInstanceWithoutConstructor();
        (new \ReflectionProperty(GoogleAnalyticsService::class, 'property'))->setValue($service, 'properties/123');
        $failed = new \ReflectionMethod(GoogleAnalyticsService::class, 'failed');

        $this->assertSame([], $failed->invoke($service, 'last7DaysTimeline', new RuntimeException('PERMISSION_DENIED'), []));

        Log::shouldHaveReceived('warning')->once()->withArgs(fn ($message, $context) =>
            str_contains($message, 'last7DaysTimeline')
            && $context['property'] === 'properties/123'
            && str_contains($context['error'], 'PERMISSION_DENIED'));
    }

    public function test_chaque_appel_api_journalise_ses_erreurs(): void
    {
        $source = file_get_contents(app_path('Services/GoogleAnalyticsService.php'));

        // Plus aucun catch qui renvoie une valeur par défaut sans passer par failed()
        $this->assertSame(0, preg_match('/catch \(Throwable \$e\) \{\s*return (?!\$this->failed)/', $source));
        $this->assertSame(9, substr_count($source, 'return $this->failed(__FUNCTION__, $e,'));
    }

    public function test_la_page_affiche_la_propriete_depuis_la_config_et_la_courbe_complete(): void
    {
        config(['services.google_analytics.property_id' => '463914808']);

        $ga = Mockery::mock(GoogleAnalyticsService::class);
        $ga->shouldReceive('monthStats')->andReturn(['users30' => 0, 'newUsers30' => 0, 'sessions30' => 0, 'bounceRate' => 0.0]);
        $ga->shouldReceive('todayUsers', 'realtimeUsers', 'avgEngagementTime')->andReturn(0);
        $ga->shouldReceive('topCountries', 'topPages', 'deviceCategories', 'trafficSources')->andReturn([]);
        $ga->shouldReceive('last7DaysTimeline')->andReturn(GoogleAnalyticsService::fillDays(['20261030' => 880], Carbon::parse('2026-11-04')));
        $this->app->instance(GoogleAnalyticsService::class, $ga);

        $response = $this->withSession(['is_admin' => true])->get(route('admin.analytics.index'))->assertOk();

        $response->assertSee('Property 463914808');
        $this->assertCount(7, $response->viewData('timeline'));
        $this->assertSame('04/11', $response->viewData('timeline')[6]['date']);
    }
}
