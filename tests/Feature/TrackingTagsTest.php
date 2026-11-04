<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Tag GA4 et pixel Meta du layout public. Régression du 31/10/2026 : l'identifiant GA était lu
 * par env() dans la vue, donc null sous config:cache, et le tag disparaissait de tout le site.
 */
class TrackingTagsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Http::preventStrayRequests();
    }

    public function test_les_identifiants_sont_des_cles_de_config(): void
    {
        $this->assertArrayHasKey('measurement_id', config('services.ga'));
        $this->assertArrayHasKey('id', config('services.facebook_pixel'));
        $this->assertSame('875478145273445', config('services.facebook_pixel.id'));
    }

    public function test_le_layout_public_charge_ga4_depuis_la_config(): void
    {
        config(['services.ga.measurement_id' => 'G-TEST12345']);

        $html = $this->get(route('marketplace.index'))->assertOk()->getContent();

        $this->assertStringContainsString('<script async src="https://www.googletagmanager.com/gtag/js?id=G-TEST12345"></script>', $html);
        $this->assertStringContainsString("gtag('config',\"G-TEST12345\");", $html);
    }

    public function test_sans_identifiant_ga_aucun_tag(): void
    {
        config(['services.ga.measurement_id' => null]);

        $this->assertStringNotContainsString('googletagmanager.com/gtag', $this->get(route('marketplace.index'))->getContent());
    }

    public function test_le_pixel_meta_utilise_l_identifiant_de_la_config(): void
    {
        config(['services.facebook_pixel.id' => '111222333444555']);

        $html = $this->get(route('marketplace.index'))->assertOk()->getContent();

        $this->assertStringContainsString("fbq('init',\"111222333444555\");fbq('track','PageView');", $html);
        $this->assertStringContainsString('facebook.com/tr?id=111222333444555&ev=PageView', $html);
        $this->assertStringNotContainsString('875478145273445', $html);
    }
}
