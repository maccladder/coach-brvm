<?php

namespace Tests\Feature;

use App\Models\AdminGrant;
use App\Models\MarketplaceProduct;
use App\Models\ProductFile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MarketplaceProductFilesMigrationTest extends TestCase
{
    use RefreshDatabase;

    private function makeProduct(string $type, string $slug): MarketplaceProduct
    {
        return MarketplaceProduct::create([
            'title'  => ucfirst($slug),
            'slug'   => $slug,
            'type'   => $type,
            'status' => 'published',
            'price'  => 5000,
        ]);
    }

    private function runBackfill(): void
    {
        $migration = require database_path('migrations/2026_10_30_000002_backfill_marketplace_product_files_from_assets.php');
        $migration->up();
    }

    public function test_backfill_reprend_les_fichiers_des_livres_et_logiciels_sans_toucher_aux_originaux(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('marketplace/files/livre.pdf', str_repeat('a', 2048));
        Storage::disk('public')->put('marketplace/files/logiciel.rar', str_repeat('b', 4096));

        $book     = $this->makeProduct('book', 'guide-brvm');
        $software = $this->makeProduct('software', 'proformine');
        $bookAsset = $book->assets()->create(['kind' => 'file', 'path' => 'marketplace/files/livre.pdf', 'label' => 'PDF', 'is_downloadable' => true]);
        $software->assets()->create(['kind' => 'file', 'path' => 'marketplace/files/logiciel.rar', 'label' => 'Logiciel', 'is_downloadable' => true]);

        $this->runBackfill();

        $file = $book->files()->sole();
        $this->assertSame($bookAsset->id, $file->legacy_asset_id);
        $this->assertSame('PDF', $file->label);
        $this->assertSame('guide-brvm.pdf', $file->original_name);
        $this->assertSame('public', $file->disk);
        $this->assertSame('marketplace/files/livre.pdf', $file->path);
        $this->assertSame(2048, $file->size);
        $this->assertSame(0, $file->download_count);

        $this->assertSame('proformine.rar', $software->files()->sole()->original_name);

        // Rien n'est déplacé ni supprimé
        Storage::disk('public')->assertExists(['marketplace/files/livre.pdf', 'marketplace/files/logiciel.rar']);
        $this->assertSame(2, $book->assets()->count() + $software->assets()->count());
    }

    public function test_backfill_ignore_videos_streams_jeux_et_est_idempotent(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('marketplace/files/source.mp4', 'mp4');
        Storage::disk('public')->put('marketplace/files/livre.pdf', 'pdf');

        $video = $this->makeProduct('video', 'formation-video');
        $video->assets()->create(['kind' => 'file', 'path' => 'marketplace/files/source.mp4', 'label' => 'Vidéo', 'is_downloadable' => true]);
        $video->assets()->create(['kind' => 'stream', 'url' => 'cf-video-id', 'label' => 'Vidéo', 'is_downloadable' => false]);
        $game = MarketplaceProduct::create([
            'title' => 'Jeu', 'slug' => 'un-jeu', 'type' => 'game', 'status' => 'published',
            'price' => 0, 'game_html' => '<!DOCTYPE html><title>Jeu</title>',
        ]);
        $book = $this->makeProduct('book', 'livre');
        $book->assets()->create(['kind' => 'file', 'path' => 'marketplace/files/livre.pdf', 'label' => 'PDF', 'is_downloadable' => true]);

        $this->runBackfill();
        $this->runBackfill();

        $this->assertSame(0, $video->files()->count());
        $this->assertSame(0, $game->files()->count());
        $this->assertSame(1, ProductFile::count());
    }

    public function test_backfill_reprend_un_asset_dont_le_fichier_physique_manque(): void
    {
        Storage::fake('public');
        $book = $this->makeProduct('book', 'livre-perdu');
        $book->assets()->create(['kind' => 'file', 'path' => 'marketplace/files/absent.pdf', 'label' => 'PDF', 'is_downloadable' => true]);

        $this->runBackfill();

        $this->assertNull($book->files()->sole()->size);
    }

    public function test_supprimer_les_assets_ne_supprime_jamais_les_fichiers_produit(): void
    {
        // update() d'un jeu fait $product->assets()->delete() : marketplace_product_files doit survivre
        $product = $this->makeProduct('software', 'vidarod');
        $asset = $product->assets()->create(['kind' => 'file', 'path' => 'marketplace/files/x.zip', 'label' => 'Logiciel', 'is_downloadable' => true]);
        $product->files()->create([
            'legacy_asset_id' => $asset->id, 'label' => 'Windows — Installeur', 'platform' => 'windows',
            'original_name' => 'vidarod-setup.exe', 'disk' => 'local', 'path' => 'marketplace/product-files/1/a.exe',
        ]);

        $product->assets()->delete();

        $file = $product->files()->sole();
        $this->assertNull($file->legacy_asset_id);
        $this->assertSame('vidarod-setup.exe', $file->original_name);
    }

    public function test_files_tries_par_sort_order_et_supprimes_avec_le_produit(): void
    {
        $product = $this->makeProduct('software', 'vidarod');
        foreach ([['macOS', 2], ['Windows — Installeur', 0], ['Windows — Portable', 1]] as [$label, $order]) {
            $product->files()->create([
                'label' => $label, 'original_name' => "$label.zip", 'path' => "p/$order.zip", 'sort_order' => $order,
            ]);
        }

        $this->assertSame(['Windows — Installeur', 'Windows — Portable', 'macOS'], $product->files()->pluck('label')->all());

        $product->delete();
        $this->assertSame(0, ProductFile::count());
    }

    public function test_is_accessible_by_achat_paye_ou_attribution_admin(): void
    {
        $product = $this->makeProduct('software', 'vidarod');

        $buyer = User::factory()->create();
        $buyer->purchasedProducts()->attach($product->id, ['status' => 'paid', 'amount' => 5000, 'paid_at' => now()]);

        $pending = User::factory()->create();
        $pending->purchasedProducts()->attach($product->id, ['status' => 'pending', 'amount' => 5000]);

        $granted = User::factory()->create();
        AdminGrant::create([
            'user_id' => $granted->id, 'grantable_type' => MarketplaceProduct::class, 'grantable_id' => $product->id,
            'granted_at' => now(),
        ]);

        $revoked = User::factory()->create();
        AdminGrant::create([
            'user_id' => $revoked->id, 'grantable_type' => MarketplaceProduct::class, 'grantable_id' => $product->id,
            'granted_at' => now()->subDay(), 'revoked_at' => now(),
        ]);

        $this->assertTrue($product->isAccessibleBy($buyer));
        $this->assertTrue($product->isAccessibleBy($granted));
        $this->assertFalse($product->isAccessibleBy($revoked));
        $this->assertFalse($product->isAccessibleBy($pending));
        $this->assertFalse($product->isAccessibleBy(User::factory()->create()));
        $this->assertFalse($product->isAccessibleBy(null));
    }

    public function test_human_size_et_platform_label(): void
    {
        $file = new ProductFile(['size' => 88_400_000, 'platform' => 'windows_portable']);

        $this->assertSame('84,3 Mo', $file->human_size);
        $this->assertSame('Windows (portable)', $file->platform_label);
        $this->assertNull((new ProductFile())->human_size);
    }
}
