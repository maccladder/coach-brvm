<?php

namespace Tests\Feature;

use App\Models\MarketplaceProduct;
use App\Models\ProductFile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MarketplaceMoveFilesPrivateTest extends TestCase
{
    use RefreshDatabase;

    /** PDF d'une page, lisible par Imagick/Ghostscript. */
    private const TINY_PDF = "%PDF-1.4\n1 0 obj<</Type/Catalog/Pages 2 0 R>>endobj\n2 0 obj<</Type/Pages/Kids[3 0 R]/Count 1>>endobj\n3 0 obj<</Type/Page/Parent 2 0 R/MediaBox[0 0 200 200]>>endobj\ntrailer<</Root 1 0 R>>\n%%EOF";

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        Storage::fake('public');
    }

    /** Livre « ancien système » : asset + ligne reprise, fichier sur le disque public. */
    private function legacyBook(string $slug, string $path, ?string $content = 'contenu-pdf'): array
    {
        $product = MarketplaceProduct::create([
            'title' => ucfirst($slug), 'slug' => $slug, 'type' => 'book', 'status' => 'published', 'price' => 3000,
        ]);
        if ($content !== null) {
            Storage::disk('public')->put($path, $content);
        }
        $asset = $product->assets()->create(['kind' => 'file', 'path' => $path, 'label' => 'PDF', 'is_downloadable' => true]);
        $file  = $product->files()->create([
            'legacy_asset_id' => $asset->id, 'label' => 'PDF', 'original_name' => "{$slug}.pdf",
            'disk' => 'public', 'path' => $path, 'size' => $content !== null ? strlen($content) : null,
        ]);

        return [$product, $asset, $file];
    }

    public function test_dry_run_ne_modifie_rien(): void
    {
        [, $asset, $file] = $this->legacyBook('guide', 'marketplace/files/guide.pdf');

        $this->artisan('marketplace:move-files-private', ['--dry-run' => true])
            ->expectsOutputToContain('dry-run')
            ->expectsTable(['Résumé', 'Nombre'], [['À déplacer', 1], ['Déjà privés', 0], ['Manquants', 0], ['Erreurs', 0]])
            ->assertSuccessful();

        $this->assertSame('public', $file->fresh()->disk);
        $this->assertSame('public', $asset->fresh()->disk);
        Storage::disk('public')->assertExists('marketplace/files/guide.pdf');
        $this->assertEmpty(Storage::disk('local')->allFiles());
    }

    public function test_deplace_vers_le_disque_prive_met_a_jour_fichier_et_asset_puis_supprime_l_original(): void
    {
        [$product, $asset, $file] = $this->legacyBook('guide', 'marketplace/files/guide.pdf', 'contenu-du-livre');
        $this->legacyBook('perdu', 'marketplace/files/perdu.pdf', null);   // fichier physique manquant

        $this->artisan('marketplace:move-files-private')
            ->expectsTable(['Résumé', 'Nombre'], [['Déplacés', 1], ['Déjà privés', 0], ['Manquants', 1], ['Erreurs', 0]])
            ->assertSuccessful();

        $file->refresh();
        $asset->refresh();
        $expected = "marketplace/product-files/{$product->id}/guide.pdf";

        $this->assertSame(['local', $expected], [$file->disk, $file->path]);
        $this->assertSame(['local', $expected], [$asset->disk, $asset->path]);
        $this->assertSame('contenu-du-livre', Storage::disk('local')->get($expected));
        Storage::disk('public')->assertMissing('marketplace/files/guide.pdf');

        // L'acheteur télécharge toujours, depuis le disque privé
        $buyer = User::factory()->create();
        $buyer->purchasedProducts()->attach($product->id, ['status' => 'paid', 'amount' => 3000, 'paid_at' => now()]);
        $this->actingAs($buyer)->get(route('marketplace.files.download', [$product, $file]))
            ->assertOk()->assertDownload('guide.pdf');

        // Idempotente : la 2e exécution ne trouve plus rien à déplacer
        $this->artisan('marketplace:move-files-private')
            ->expectsTable(['Résumé', 'Nombre'], [['Déplacés', 0], ['Déjà privés', 1], ['Manquants', 1], ['Erreurs', 0]])
            ->assertSuccessful();
    }

    public function test_reprend_une_copie_interrompue_sans_doublon(): void
    {
        [$product, , $file] = $this->legacyBook('guide', 'marketplace/files/guide.pdf', 'contenu');
        // Copie partielle laissée par une exécution interrompue
        Storage::disk('local')->put("marketplace/product-files/{$product->id}/guide.pdf", 'cont');

        $this->artisan('marketplace:move-files-private')->assertSuccessful();

        $this->assertSame('contenu', Storage::disk('local')->get($file->fresh()->path));
        Storage::disk('public')->assertMissing('marketplace/files/guide.pdf');
    }

    public function test_plus_aucun_fichier_payant_sous_storage_marketplace_files_apres_deplacement(): void
    {
        $this->legacyBook('a', 'marketplace/files/a.pdf');
        $this->legacyBook('b', 'marketplace/files/b.pdf');

        $this->artisan('marketplace:move-files-private')->assertSuccessful();

        $this->assertEmpty(Storage::disk('public')->allFiles('marketplace/files'));
        $this->assertSame(0, ProductFile::where('disk', 'public')->count());

        // Le disque privé n'est pas servi par /storage sans URL signée
        $this->get('/storage/' . ProductFile::first()->path)->assertForbidden();
    }

    public function test_apercus_pdf_generes_depuis_un_pdf_sur_le_disque_prive(): void
    {
        if (!extension_loaded('imagick')) {
            $this->markTestSkipped('Imagick absent.');
        }
        try {
            $probe = tempnam(sys_get_temp_dir(), 'pdf');
            file_put_contents($probe, self::TINY_PDF);
            (new \Imagick())->readImage($probe . '[0]');
        } catch (\Throwable $e) {
            $this->markTestSkipped('Imagick ne lit pas les PDF ici (Ghostscript absent).');
        }

        $this->withSession(['is_admin' => true])->post(route('admin.marketplace.store'), [
            'title' => 'Livre privé', 'type' => 'book', 'price' => 2000, 'status' => 'published',
            'file'  => UploadedFile::fake()->createWithContent('livre.pdf', self::TINY_PDF),
        ])->assertSessionHasNoErrors();

        $product = MarketplaceProduct::where('title', 'Livre privé')->sole();
        $this->assertSame('local', $product->files()->sole()->disk);
        $this->assertSame(1, $product->pages_count);
        Storage::disk('public')->assertExists("marketplace/previews/{$product->id}/p1.jpg");

        // Commande de rattrapage des aperçus : elle aussi lit le PDF privé
        Storage::disk('public')->deleteDirectory("marketplace/previews/{$product->id}");
        $this->artisan('marketplace:backfill-pdf', ['--force' => true])->assertSuccessful();
        Storage::disk('public')->assertExists("marketplace/previews/{$product->id}/p1.jpg");
    }
}
