<?php

namespace Tests\Feature;

use App\Mail\ProductFilePendingMail;
use App\Mail\ProductFilesAddedMail;
use App\Models\AdminNotification;
use App\Models\MarketplaceCategory;
use App\Models\MarketplaceProduct;
use App\Models\ProductFile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MarketplaceVendorFileReviewTest extends TestCase
{
    use RefreshDatabase;

    private User $vendor;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        Storage::fake('public');
        Mail::fake();

        $this->vendor = User::factory()->create();
        $this->vendor->forceFill(['is_vendor' => true, 'vendor_status' => 'approved'])->save();
    }

    private function product(string $status = 'published', string $type = 'software'): MarketplaceProduct
    {
        return MarketplaceProduct::create([
            'user_id' => $this->vendor->id, 'title' => 'Vidarod', 'slug' => 'vidarod-' . uniqid(),
            'type' => $type, 'status' => $status, 'price' => 15000,
            'category_id' => MarketplaceCategory::firstOrCreate(['slug' => 'logiciels'], ['name' => 'Logiciels'])->id,
        ]);
    }

    private function buyer(MarketplaceProduct $product): User
    {
        $user = User::factory()->create();
        $user->purchasedProducts()->attach($product->id, ['status' => 'paid', 'amount' => 15000, 'paid_at' => now()]);

        return $user;
    }

    private function asVendor()
    {
        return $this->actingAs($this->vendor)->withSession(['view_mode' => 'vendor']);
    }

    private function vendorUpload(MarketplaceProduct $product, array $files)
    {
        return $this->asVendor()->post(route('vendor.products.files.store', $product), ['files' => $files]);
    }

    private function approvedFile(MarketplaceProduct $product, array $attrs = []): ProductFile
    {
        $path = "marketplace/product-files/{$product->id}/" . uniqid() . '.exe';
        Storage::disk('local')->put($path, 'v1');

        return $product->files()->create($attrs + [
            'label' => 'Windows — Installeur', 'platform' => 'windows', 'original_name' => 'Vidarod-1.0.exe',
            'disk' => 'local', 'path' => $path, 'size' => 2,
        ]);
    }

    public function test_executable_ajoute_par_un_vendeur_sur_produit_publie_reste_invisible(): void
    {
        $product = $this->product();
        $buyer   = $this->buyer($product);

        $this->vendorUpload($product, [UploadedFile::fake()->create('Vidarod-Setup.exe', 20)])
            ->assertSessionHas('pf_success', fn ($m) => str_contains($m, 'en attente de validation'));

        $file = $product->files()->sole();
        $this->assertSame('pending', $file->status);

        $this->actingAs($buyer)->get(route('marketplace.files.download', [$product, $file]))->assertNotFound();
        $this->actingAs($buyer)->get(route('marketplace.show', $product->slug))
            ->assertOk()
            ->assertDontSee(route('marketplace.files.download', [$product, $file]), false);
        $this->actingAs($buyer)->get(route('my.products'))
            ->assertDontSee(route('marketplace.files.download', [$product, $file]), false);
        // L'ancienne URL ne retombe pas sur un asset ni sur le fichier en attente
        $this->actingAs($buyer)->get(route('my.products.download', $product))->assertNotFound();

        // L'admin est prévenu (centre de notifications + email)
        $this->assertTrue(AdminNotification::where('type', 'product_file_pending')->exists());
        Mail::assertQueued(ProductFilePendingMail::class, fn ($m) => $m->file->is($file)
            && $m->hasTo('maccladder@gmail.com'));

        $mail = new ProductFilePendingMail($file);
        $mail->assertHasSubject('🕒 Fichier vendeur à valider : Vidarod – Boursiv');
        $mail->assertSeeInHtml('Vidarod-Setup.exe');
        $mail->assertSeeInHtml(route('admin.marketplace.edit', $product), false);
    }

    public function test_toutes_les_extensions_executables_sont_soumises_a_validation(): void
    {
        $product = $this->product();

        $this->vendorUpload($product, collect(['exe', 'msi', 'dmg', 'pkg', 'apk', 'zip', 'rar', '7z'])
            ->map(fn ($ext) => UploadedFile::fake()->create("app.$ext", 5))->all());

        $this->assertSame(8, $product->files()->where('status', 'pending')->count());
        Mail::assertQueuedCount(8);
    }

    public function test_pdf_et_epub_d_un_vendeur_sont_publies_sans_validation(): void
    {
        $product = $this->product(type: 'book');

        $this->vendorUpload($product, [
            UploadedFile::fake()->create('guide.pdf', 5),
            UploadedFile::fake()->create('guide.epub', 5),
        ]);

        $this->assertSame(['approved', 'approved'], $product->files()->pluck('status')->all());
        Mail::assertNothingQueued();
    }

    public function test_executable_sur_produit_brouillon_et_ajout_admin_sont_approuves_directement(): void
    {
        $draft = $this->product('draft');
        $this->vendorUpload($draft, [UploadedFile::fake()->create('app.exe', 5)]);
        $this->assertSame('approved', $draft->files()->sole()->status);

        $published = $this->product();
        $this->withSession(['is_admin' => true])
            ->post(route('admin.marketplace.files.store', $published), ['files' => [UploadedFile::fake()->create('app.exe', 5)]]);
        $this->assertSame('approved', $published->files()->sole()->status);

        Mail::assertNotQueued(ProductFilePendingMail::class);
    }

    public function test_admin_approuve_et_previent_les_acheteurs(): void
    {
        $product = $this->product();
        $buyer   = $this->buyer($product);
        $this->vendorUpload($product, [UploadedFile::fake()->create('Vidarod.dmg', 10)]);
        $file = $product->files()->sole();

        $this->withSession(['is_admin' => true])
            ->post(route('admin.marketplace.files.approve', [$product, $file]), ['notify' => 1])
            ->assertSessionHas('pf_success');

        $this->assertSame('approved', $file->fresh()->status);
        $this->assertNotNull($file->fresh()->reviewed_at);
        $this->actingAs($buyer)->get(route('marketplace.files.download', [$product, $file]))
            ->assertOk()->assertDownload('Vidarod.dmg');
        Mail::assertQueued(ProductFilesAddedMail::class, fn ($m) => $m->hasTo($buyer->email));
    }

    public function test_admin_refuse_avec_motif_visible_par_le_vendeur(): void
    {
        $product = $this->product();
        $buyer   = $this->buyer($product);
        $this->vendorUpload($product, [UploadedFile::fake()->create('app.exe', 10)]);
        $file = $product->files()->sole();

        $this->withSession(['is_admin' => true])
            ->post(route('admin.marketplace.files.reject', [$product, $file]), ['review_note' => 'Détecté comme malveillant par VirusTotal'])
            ->assertSessionHas('pf_success');

        $file->refresh();
        $this->assertSame('rejected', $file->status);
        $this->actingAs($buyer)->get(route('marketplace.files.download', [$product, $file]))->assertNotFound();

        $this->flushSession();
        $this->asVendor()->get(route('vendor.products.edit', $product))
            ->assertOk()
            ->assertSee('Refusé')
            ->assertSee('Détecté comme malveillant par VirusTotal')
            ->assertDontSee('Motif du refus');   // pas de boutons de validation côté vendeur
    }

    public function test_edition_admin_affiche_les_boutons_de_validation(): void
    {
        $product = $this->product();
        $this->vendorUpload($product, [UploadedFile::fake()->create('app.exe', 10)]);
        $file = $product->files()->sole();

        $this->flushSession();
        $this->withSession(['is_admin' => true])->get(route('admin.marketplace.edit', $product))
            ->assertOk()
            ->assertSee('1 en attente de validation')
            ->assertSee(route('admin.marketplace.files.approve', [$product, $file]), false)
            ->assertSee(route('admin.marketplace.files.reject', [$product, $file]), false);
    }

    public function test_stagiaire_et_vendeur_ne_peuvent_pas_valider(): void
    {
        $product = $this->product();
        $this->vendorUpload($product, [UploadedFile::fake()->create('app.exe', 10)]);
        $file = $product->files()->sole();

        $this->flushSession();
        $this->withSession(['is_stagiaire' => true])
            ->post(route('admin.marketplace.files.approve', [$product, $file]))->assertForbidden();

        $this->flushSession();
        $this->actingAs($this->vendor)->withSession(['view_mode' => 'vendor'])
            ->post(route('admin.marketplace.files.approve', [$product, $file]))
            ->assertRedirect(route('admin.login.form'));

        $this->assertSame('pending', $file->fresh()->status);
    }

    public function test_remplacement_du_fichier_principal_garde_l_ancien_jusqu_a_l_approbation(): void
    {
        $product = $this->product();
        $buyer   = $this->buyer($product);
        Storage::disk('local')->put('marketplace/product-files/old.zip', 'version-1');
        $asset = $product->assets()->create(['kind' => 'file', 'disk' => 'local', 'path' => 'marketplace/product-files/old.zip', 'label' => 'Logiciel', 'is_downloadable' => true]);
        $current = $product->files()->create([
            'legacy_asset_id' => $asset->id, 'label' => 'Windows — Installeur', 'platform' => 'windows',
            'original_name' => 'Vidarod-1.0.zip', 'disk' => 'local', 'path' => 'marketplace/product-files/old.zip', 'size' => 9,
        ]);

        $this->asVendor()->put(route('vendor.products.update', $product), [
            'category_id' => $product->category_id, 'title' => 'Vidarod', 'type' => 'software', 'price' => 15000,
            'file' => UploadedFile::fake()->createWithContent('Vidarod-2.0.zip', 'version-2'),
        ])->assertSessionHas('success', fn ($m) => str_contains($m, 'validation admin'));

        $pending = $product->files()->where('status', 'pending')->sole();
        $this->assertSame($current->id, $pending->replaces_file_id);

        // Avant approbation : l'acheteur reçoit toujours la v1, l'asset n'a pas bougé
        $this->assertSame('marketplace/product-files/old.zip', $asset->fresh()->path);
        $this->actingAs($buyer)->get(route('marketplace.files.download', [$product, $current]))
            ->assertOk()->assertDownload('Vidarod-1.0.zip');
        Mail::assertQueued(ProductFilePendingMail::class);

        $this->flushSession();
        $this->withSession(['is_admin' => true])->post(route('admin.marketplace.files.approve', [$product, $pending]));

        // Après : même ligne (même URL, même libellé), nouveau contenu, ancien fichier effacé
        $current->refresh();
        $this->assertModelMissing($pending);
        $this->assertSame('Windows — Installeur', $current->label);
        $this->assertSame('Vidarod-2.0.zip', $current->original_name);
        $this->assertSame($pending->path, $current->path);
        $this->assertSame($pending->path, $asset->fresh()->path);
        Storage::disk('local')->assertMissing('marketplace/product-files/old.zip');
        $this->assertSame('version-2', Storage::disk('local')->get($current->path));
        $this->assertSame(1, $product->files()->count());
    }

    public function test_nouveau_remplacement_annule_le_precedent_en_attente(): void
    {
        $product = $this->product();
        $current = $this->approvedFile($product);
        $files   = app(\App\Services\Marketplace\ProductFileService::class);

        $first  = $files->stagePendingReplacement($product, UploadedFile::fake()->create('v2.exe', 5), $current);
        $second = $files->stagePendingReplacement($product, UploadedFile::fake()->create('v3.exe', 5), $current);

        $this->assertModelMissing($first);
        Storage::disk('local')->assertMissing($first->path);
        $this->assertSame($current->id, $second->replaces_file_id);
        $this->assertSame(2, $product->files()->count());
    }
}
