<?php

namespace Tests\Feature;

use App\Models\MarketplaceProduct;
use App\Services\Marketplace\VignetteService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class VignettesMarketplaceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
    }

    /** Couverture PNG de grande taille (comme les vraies : 1920×1440). */
    private function couverture(string $chemin, int $w = 1920, int $h = 1440): string
    {
        $img = imagecreatetruecolor($w, $h);
        imagefill($img, 0, 0, imagecolorallocate($img, 200, 120, 40));
        ob_start(); imagepng($img); $png = ob_get_clean();
        Storage::disk('public')->put($chemin, $png);

        return $chemin;
    }

    private function jeu(string $titre, ?string $cover, array $attrs = []): MarketplaceProduct
    {
        return MarketplaceProduct::create(array_merge([
            'title' => $titre, 'slug' => \Illuminate\Support\Str::slug($titre), 'type' => 'game',
            'status' => 'published', 'price' => 0, 'game_html' => '<html></html>', 'cover_image_path' => $cover,
        ], $attrs));
    }

    public function test_command_generates_light_400x300_thumbnails_without_touching_originals(): void
    {
        $cover = $this->couverture('marketplace/covers/garba-master.png');
        $avant = Storage::disk('public')->get($cover);
        MarketplaceProduct::withoutEvents(fn () => $this->jeu('Garba Master', $cover));

        $this->artisan('marketplace:vignettes')->assertSuccessful();

        $vignette = app(VignetteService::class)->chemin($cover);
        Storage::disk('public')->assertExists($vignette);
        [$w, $h] = getimagesizefromstring(Storage::disk('public')->get($vignette));
        $this->assertSame([400, 300], [$w, $h]);
        $this->assertLessThan(60 * 1024, Storage::disk('public')->size($vignette));
        $this->assertSame($avant, Storage::disk('public')->get($cover)); // original intact
    }

    public function test_portrait_and_square_covers_are_center_cropped_to_4_3(): void
    {
        foreach (['portrait' => [800, 1000], 'carre' => [1024, 1024]] as $nom => [$w, $h]) {
            $cover = $this->couverture("marketplace/covers/$nom.png", $w, $h);
            $chemin = app(VignetteService::class)->generer($cover);

            $this->assertSame([400, 300], array_slice(getimagesizefromstring(Storage::disk('public')->get($chemin)), 0, 2), $nom);
        }
    }

    public function test_by_default_only_games_unless_tous(): void
    {
        $jeu   = $this->couverture('marketplace/covers/jeu.png', 40, 30);
        $cours = $this->couverture('marketplace/covers/cours.png', 40, 30);
        MarketplaceProduct::withoutEvents(function () use ($jeu, $cours) {
            $this->jeu('Jeu', $jeu);
            $this->jeu('Cours', $cours, ['type' => 'course']);
        });
        $svc = app(VignetteService::class);

        $this->artisan('marketplace:vignettes')->assertSuccessful();
        Storage::disk('public')->assertExists($svc->chemin($jeu));
        Storage::disk('public')->assertMissing($svc->chemin($cours));

        $this->artisan('marketplace:vignettes --tous')->assertSuccessful();
        Storage::disk('public')->assertExists($svc->chemin($cours));
    }

    public function test_thumbnail_generated_automatically_when_cover_is_set_or_changed(): void
    {
        $svc = app(VignetteService::class);
        $c1  = $this->couverture('marketplace/covers/v1.png', 80, 60);
        $jeu = $this->jeu('Gri-gri', $c1);
        Storage::disk('public')->assertExists($svc->chemin($c1));

        $c2 = $this->couverture('marketplace/covers/v2.png', 80, 60);
        $jeu->update(['cover_image_path' => $c2]);
        Storage::disk('public')->assertExists($svc->chemin($c2));
    }

    public function test_url_is_null_when_thumbnail_missing_never_the_original(): void
    {
        $cover = $this->couverture('marketplace/covers/sans-vignette.png', 40, 30);
        $jeu   = MarketplaceProduct::withoutEvents(fn () => $this->jeu('Sans vignette', $cover));

        $this->assertNull($jeu->vignette_url);
        $this->assertNull($this->jeu('Sans couverture', null)->vignette_url);

        app(VignetteService::class)->generer($cover);
        $this->assertStringContainsString('/storage/marketplace/covers/thumbs/sans-vignette-400.', $jeu->fresh()->vignette_url);
    }

    public function test_missing_or_unreadable_cover_does_not_crash(): void
    {
        Storage::disk('public')->put('marketplace/covers/casse.png', 'pas une image');
        MarketplaceProduct::withoutEvents(function () {
            $this->jeu('Introuvable', 'marketplace/covers/absent.png');
            $this->jeu('Cassé', 'marketplace/covers/casse.png');
        });

        $this->artisan('marketplace:vignettes')->assertFailed()->expectsOutputToContain('0 / 2');
    }
}
