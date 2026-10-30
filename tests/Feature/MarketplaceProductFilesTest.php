<?php

namespace Tests\Feature;

use App\Mail\ProductFilesAddedMail;
use App\Models\AdminGrant;
use App\Models\MarketplaceProduct;
use App\Models\ProductFile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MarketplaceProductFilesTest extends TestCase
{
    use RefreshDatabase;

    private const WINDOWS_UA = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/130.0 Safari/537.36';

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        Storage::fake('public');
    }

    private function makeProduct(array $attrs = []): MarketplaceProduct
    {
        return MarketplaceProduct::create($attrs + [
            'title'  => 'Vidarod',
            'slug'   => 'vidarod-' . uniqid(),
            'type'   => 'software',
            'status' => 'published',
            'price'  => 15000,
        ]);
    }

    private function buyer(MarketplaceProduct $product, string $status = 'paid'): User
    {
        $user = User::factory()->create();
        $user->purchasedProducts()->attach($product->id, [
            'status' => $status, 'amount' => $product->price, 'paid_at' => $status === 'paid' ? now() : null,
        ]);

        return $user;
    }

    private function grant(MarketplaceProduct $product, User $user, bool $revoked = false): void
    {
        AdminGrant::create([
            'user_id' => $user->id, 'grantable_type' => MarketplaceProduct::class, 'grantable_id' => $product->id,
            'granted_at' => now()->subDay(), 'revoked_at' => $revoked ? now() : null,
        ]);
    }

    private function vendor(): User
    {
        $user = User::factory()->create();
        $user->forceFill(['is_vendor' => true, 'vendor_status' => 'approved'])->save();

        return $user;
    }

    private function storedFile(MarketplaceProduct $product, array $attrs = []): ProductFile
    {
        $path = "marketplace/product-files/{$product->id}/" . uniqid() . '.exe';
        Storage::disk('local')->put($path, 'binaire');

        return $product->files()->create($attrs + [
            'label' => 'Windows — Installeur', 'platform' => 'windows', 'original_name' => 'vidarod-setup.exe',
            'disk' => 'local', 'path' => $path, 'size' => 7,
        ]);
    }

    private function adminUpload(MarketplaceProduct $product, array $files, array $extra = [], array $session = ['is_admin' => true])
    {
        return $this->withSession($session)->post(route('admin.marketplace.files.store', $product), $extra + ['files' => $files]);
    }

    private function downloadUrl(MarketplaceProduct $product, ProductFile $file): string
    {
        return route('marketplace.files.download', [$product, $file]);
    }

    // ── Accès acheteur ───────────────────────────────────────────

    public function test_acheteur_avant_ajout_peut_telecharger_le_nouveau_fichier(): void
    {
        $product = $this->makeProduct();
        $buyer   = $this->buyer($product);   // achète AVANT l'ajout

        $this->adminUpload($product, [UploadedFile::fake()->create('Vidarod-Setup-2.0.exe', 2048)], [
            'labels' => ['Windows — Installeur'], 'platforms' => ['windows'],
        ])->assertRedirect(route('admin.marketplace.edit', $product) . '#fichiers');

        $file = $product->files()->sole();
        $this->assertSame('local', $file->disk);
        Storage::disk('local')->assertExists($file->path);
        $this->assertStringStartsWith("marketplace/product-files/{$product->id}/", $file->path);

        $this->actingAs($buyer)->get($this->downloadUrl($product, $file))
            ->assertOk()
            ->assertDownload('Vidarod-Setup-2.0.exe');

        $this->assertSame(1, $file->fresh()->download_count);
    }

    public function test_non_acheteur_et_achat_en_attente_recoivent_403(): void
    {
        $product = $this->makeProduct();
        $file    = $this->storedFile($product);

        $this->actingAs(User::factory()->create())->get($this->downloadUrl($product, $file))->assertForbidden();
        $this->actingAs($this->buyer($product, 'pending'))->get($this->downloadUrl($product, $file))->assertForbidden();

        $revoked = User::factory()->create();
        $this->grant($product, $revoked, revoked: true);
        $this->actingAs($revoked)->get($this->downloadUrl($product, $file))->assertForbidden();

        $this->assertSame(0, $file->fresh()->download_count);
    }

    public function test_invite_redirige_vers_la_connexion(): void
    {
        $product = $this->makeProduct();
        $this->get($this->downloadUrl($product, $this->storedFile($product)))->assertRedirect(route('login'));
    }

    public function test_fichier_d_un_autre_produit_renvoie_404(): void
    {
        $product = $this->makeProduct();
        $other   = $this->makeProduct();
        $buyer   = $this->buyer($product);
        $foreign = $this->storedFile($other);

        $this->actingAs($buyer)->get($this->downloadUrl($product, $foreign))->assertNotFound();
    }

    public function test_attribution_admin_donne_acces_a_tous_les_fichiers(): void
    {
        $product = $this->makeProduct();
        $user    = User::factory()->create();
        $this->grant($product, $user);
        $win = $this->storedFile($product);
        $mac = $this->storedFile($product, ['label' => 'macOS', 'platform' => 'macos', 'original_name' => 'Vidarod.dmg', 'sort_order' => 1]);

        $this->actingAs($user)->get($this->downloadUrl($product, $win))->assertOk();
        $this->actingAs($user)->get($this->downloadUrl($product, $mac))->assertOk()->assertDownload('Vidarod.dmg');
    }

    public function test_ancienne_url_redirige_vers_le_premier_fichier(): void
    {
        $product = $this->makeProduct();
        $buyer   = $this->buyer($product);
        $second  = $this->storedFile($product, ['sort_order' => 1]);
        $first   = $this->storedFile($product, ['sort_order' => 0]);

        $this->actingAs($buyer)->get(route('my.products.download', $product))
            ->assertRedirect($this->downloadUrl($product, $first));
        $this->assertNotSame($first->id, $second->id);
    }

    public function test_ancienne_url_sans_fichier_produit_garde_l_ancien_comportement(): void
    {
        $product = $this->makeProduct(['type' => 'book', 'slug' => 'guide-brvm']);
        Storage::disk('public')->put('marketplace/files/guide.pdf', '%PDF');
        $product->assets()->create(['kind' => 'file', 'path' => 'marketplace/files/guide.pdf', 'label' => 'PDF', 'is_downloadable' => true]);

        $this->actingAs($this->buyer($product))->get(route('my.products.download', $product))
            ->assertOk()->assertDownload('guide-brvm.pdf');
    }

    // ── Gestionnaire admin ───────────────────────────────────────

    public function test_admin_ajoute_plusieurs_fichiers_avec_plateforme_devinee_sans_js(): void
    {
        $product = $this->makeProduct();

        $this->adminUpload($product, [
            UploadedFile::fake()->create('Vidarod-Setup.exe', 100),
            UploadedFile::fake()->create('Vidarod.dmg', 100),
            UploadedFile::fake()->create('Vidarod-portable.zip', 100),
        ])->assertSessionHasNoErrors();

        $files = $product->files()->get();
        $this->assertSame(['windows', 'macos', null], $files->pluck('platform')->all());
        $this->assertSame([0, 1, 2], $files->pluck('sort_order')->all());
        $this->assertSame(['Windows', 'macOS', 'Vidarod-portable'], $files->pluck('label')->all());
    }

    public function test_admin_upload_refuse_une_extension_non_autorisee_et_un_fichier_trop_gros(): void
    {
        $product = $this->makeProduct();
        config(['marketplace.files_max_mb' => 1]);

        $this->adminUpload($product, [UploadedFile::fake()->create('virus.bat', 10)])
            ->assertSessionHasErrors(['files.0'], null, 'productFiles');
        $this->adminUpload($product, [UploadedFile::fake()->create('gros.zip', 2048)])
            ->assertSessionHasErrors(['files.0'], null, 'productFiles');

        $this->assertSame(0, $product->files()->count());
        $this->assertEmpty(Storage::disk('local')->allFiles());
    }

    public function test_extensions_rar_et_7z_acceptees(): void
    {
        $product = $this->makeProduct();

        $this->adminUpload($product, [
            UploadedFile::fake()->create('pack.rar', 10),
            UploadedFile::fake()->create('pack.7z', 10),
        ])->assertSessionHasNoErrors();

        $this->assertSame(2, $product->files()->count());
    }

    public function test_admin_modifie_reordonne_et_supprime_un_fichier(): void
    {
        $product = $this->makeProduct();
        $a = $this->storedFile($product, ['label' => 'A', 'sort_order' => 0]);
        $b = $this->storedFile($product, ['label' => 'B', 'sort_order' => 1]);
        $admin = ['is_admin' => true];

        $this->withSession($admin)->patch(route('admin.marketplace.files.update', [$product, $a]), [
            'label' => 'Windows — Portable', 'platform' => 'windows_portable',
        ])->assertSessionHasNoErrors();
        $this->assertSame('windows_portable', $a->fresh()->platform);

        $this->withSession($admin)->post(route('admin.marketplace.files.move', [$product, $b]), ['direction' => 'up']);
        $this->assertSame(['B', 'Windows — Portable'], $product->files()->pluck('label')->all());

        $this->withSession($admin)->delete(route('admin.marketplace.files.destroy', [$product, $a]));
        $this->assertModelMissing($a);
        Storage::disk('local')->assertMissing($a->path);
        Storage::disk('local')->assertExists($b->path);
    }

    public function test_supprimer_un_fichier_repris_supprime_aussi_son_asset(): void
    {
        $product = $this->makeProduct(['type' => 'book']);
        Storage::disk('public')->put('marketplace/files/livre.pdf', '%PDF');
        $asset = $product->assets()->create(['kind' => 'file', 'path' => 'marketplace/files/livre.pdf', 'label' => 'PDF', 'is_downloadable' => true]);
        $file  = $product->files()->create([
            'legacy_asset_id' => $asset->id, 'label' => 'PDF', 'original_name' => 'livre.pdf', 'disk' => 'public', 'path' => 'marketplace/files/livre.pdf',
        ]);

        $this->withSession(['is_admin' => true])->delete(route('admin.marketplace.files.destroy', [$product, $file]));

        $this->assertModelMissing($asset);
        Storage::disk('public')->assertMissing('marketplace/files/livre.pdf');
    }

    public function test_action_admin_sur_un_fichier_d_un_autre_produit_renvoie_404(): void
    {
        $product = $this->makeProduct();
        $foreign = $this->storedFile($this->makeProduct());

        $this->withSession(['is_admin' => true])
            ->delete(route('admin.marketplace.files.destroy', [$product, $foreign]))
            ->assertNotFound();
        $this->assertModelExists($foreign);
    }

    public function test_routes_admin_exigent_la_session_admin(): void
    {
        $product = $this->makeProduct();

        $this->post(route('admin.marketplace.files.store', $product), ['files' => [UploadedFile::fake()->create('a.exe', 1)]])
            ->assertRedirect(route('admin.login.form'));
        $this->assertSame(0, $product->files()->count());
    }

    public function test_remplacer_le_fichier_principal_met_a_jour_la_ligne_reprise(): void
    {
        $product = $this->makeProduct(['type' => 'software', 'slug' => 'proformine']);
        Storage::disk('public')->put('marketplace/files/old.rar', 'ancien');
        $asset = $product->assets()->create(['kind' => 'file', 'path' => 'marketplace/files/old.rar', 'label' => 'Logiciel', 'is_downloadable' => true]);
        $file  = $product->files()->create([
            'legacy_asset_id' => $asset->id, 'label' => 'Logiciel', 'original_name' => 'proformine.rar', 'disk' => 'public', 'path' => 'marketplace/files/old.rar',
        ]);

        $this->withSession(['is_admin' => true])->put(route('admin.marketplace.update', $product), [
            'title' => 'Proformine', 'type' => 'software', 'price' => 5000, 'status' => 'published',
            'file'  => UploadedFile::fake()->create('Proformine-3.0.zip', 50),
        ])->assertSessionHasNoErrors();

        $file->refresh();
        $asset->refresh();
        $this->assertSame('local', $file->disk);
        $this->assertSame($asset->path, $file->path);
        $this->assertSame('local', $asset->disk);
        $this->assertSame('Proformine-3.0.zip', $file->original_name);
        Storage::disk('local')->assertExists($file->path);
        // L'ancien fichier public n'est plus exposé
        Storage::disk('public')->assertMissing('marketplace/files/old.rar');
        $this->assertSame(1, $product->files()->count());
    }

    public function test_creation_admin_d_un_logiciel_cree_sa_ligne_fichier_sur_le_disque_prive(): void
    {
        $this->withSession(['is_admin' => true])->post(route('admin.marketplace.store'), [
            'title' => 'Vidarod Pro', 'type' => 'software', 'price' => 20000, 'status' => 'draft',
            'file'  => UploadedFile::fake()->create('Vidarod-Setup.exe', 50),
        ])->assertSessionHasNoErrors();

        $product = MarketplaceProduct::where('title', 'Vidarod Pro')->sole();
        $file    = $product->files()->sole();
        $this->assertSame('local', $file->disk);
        $this->assertSame('local', $product->assets()->sole()->disk);
        $this->assertEmpty(Storage::disk('public')->allFiles('marketplace/files'));
    }

    public function test_vrai_update_d_un_jeu_laisse_les_fichiers_produit_intacts(): void
    {
        $game = $this->makeProduct([
            'type' => 'game', 'slug' => 'un-jeu', 'game_html' => '<!DOCTYPE html><title>Jeu</title>',
        ]);
        $game->assets()->create(['kind' => 'file', 'path' => 'marketplace/files/x.zip', 'label' => 'Fichier', 'is_downloadable' => true]);
        $file = $this->storedFile($game);

        $this->withSession(['is_admin' => true])->put(route('admin.marketplace.update', $game), [
            'title' => 'Un jeu', 'type' => 'game', 'price' => 0, 'status' => 'published',
        ])->assertSessionHasNoErrors();

        $this->assertSame(0, $game->assets()->count());   // comportement existant conservé
        $this->assertModelExists($file);
        Storage::disk('local')->assertExists($file->path);
        $this->assertSame('<!DOCTYPE html><title>Jeu</title>', $game->fresh()->game_html);
    }

    public function test_un_jeu_html_existant_se_joue_toujours(): void
    {
        $game  = $this->makeProduct([
            'type' => 'game', 'slug' => 'un-jeu', 'price' => 0,
            'game_html' => '<!DOCTYPE html><script src="https://js.paystack.co/v1/inline.js"></script><title>Jeu</title>',
        ]);
        $buyer = $this->buyer($game);

        $this->actingAs($buyer)->get(route('my.products.play', $game))->assertOk();
        $this->actingAs($buyer)->get(route('my.products.game-html', $game))
            ->assertOk()
            ->assertSee('<title>Jeu</title>', false)
            ->assertSee(route('paystack.mock-inline'), false);
    }

    // ── Vendeur ──────────────────────────────────────────────────

    public function test_vendeur_gere_les_fichiers_de_son_produit(): void
    {
        $vendor  = $this->vendor();
        $product = $this->makeProduct(['user_id' => $vendor->id, 'status' => 'draft']);

        $this->actingAs($vendor)->withSession(['view_mode' => 'vendor'])
            ->post(route('vendor.products.files.store', $product), ['files' => [UploadedFile::fake()->create('app.apk', 10)]])
            ->assertRedirect(route('vendor.products.edit', $product) . '#fichiers');

        $this->assertSame('android', $product->files()->sole()->platform);
    }

    public function test_vendeur_remplace_son_fichier_principal_et_soumet_avec_fichiers(): void
    {
        Mail::fake();
        $vendor  = $this->vendor();
        $product = $this->makeProduct(['user_id' => $vendor->id, 'status' => 'draft', 'category_id' => \App\Models\MarketplaceCategory::create(['name' => 'Logiciels', 'slug' => 'logiciels'])->id]);
        $as      = fn () => $this->actingAs($vendor)->withSession(['view_mode' => 'vendor']);

        $as()->put(route('vendor.products.update', $product), [
            'category_id' => $product->category_id, 'title' => 'Vidarod', 'type' => 'software', 'price' => 15000,
            'file' => UploadedFile::fake()->create('Vidarod-1.0.zip', 20),
        ])->assertSessionHasNoErrors();
        $first = $product->files()->sole();

        $as()->put(route('vendor.products.update', $product), [
            'category_id' => $product->category_id, 'title' => 'Vidarod', 'type' => 'software', 'price' => 15000,
            'file' => UploadedFile::fake()->create('Vidarod-2.0.zip', 20),
        ])->assertSessionHasNoErrors();

        $updated = $product->files()->sole();
        $this->assertSame($first->id, $updated->id);
        $this->assertSame('Vidarod-2.0.zip', $updated->original_name);
        Storage::disk('local')->assertMissing($first->path);
        Storage::disk('local')->assertExists($updated->path);

        // Produit avec seulement des fichiers téléchargeables : la soumission est acceptée
        $product->assets()->delete();
        $product->update(['cover_image_path' => 'marketplace/covers/x.jpg']);
        $as()->post(route('vendor.products.submit', $product))->assertSessionHas('success');
        $this->assertSame('pending', $product->fresh()->status);
    }

    public function test_vendeur_ne_peut_pas_toucher_aux_fichiers_d_un_autre_vendeur(): void
    {
        $owner   = $this->vendor();
        $intrus  = $this->vendor();
        $product = $this->makeProduct(['user_id' => $owner->id]);
        $file    = $this->storedFile($product);
        $as      = fn () => $this->actingAs($intrus)->withSession(['view_mode' => 'vendor']);

        $as()->post(route('vendor.products.files.store', $product), ['files' => [UploadedFile::fake()->create('a.exe', 1)]])->assertForbidden();
        $as()->patch(route('vendor.products.files.update', [$product, $file]), ['label' => 'Piraté'])->assertForbidden();
        $as()->post(route('vendor.products.files.move', [$product, $file]), ['direction' => 'down'])->assertForbidden();
        $as()->delete(route('vendor.products.files.destroy', [$product, $file]))->assertForbidden();
        $as()->get(route('vendor.products.files.download', [$product, $file]))->assertForbidden();

        $this->assertSame('Windows — Installeur', $file->fresh()->label);
        $this->assertSame(1, $product->files()->count());
        Storage::disk('local')->assertExists($file->path);

        // Produit officiel (user_id null) : interdit à un vendeur qui n'est pas le propriétaire de la plateforme
        $official = $this->makeProduct();
        $as()->post(route('vendor.products.files.store', $official), ['files' => [UploadedFile::fake()->create('a.exe', 1)]])->assertForbidden();
    }

    public function test_vendeur_bloque_pendant_la_validation_et_sans_notification(): void
    {
        Mail::fake();
        $vendor  = $this->vendor();
        $product = $this->makeProduct(['user_id' => $vendor->id, 'status' => 'pending']);
        $this->buyer($product);

        $this->actingAs($vendor)->withSession(['view_mode' => 'vendor'])
            ->post(route('vendor.products.files.store', $product), ['files' => [UploadedFile::fake()->create('a.exe', 1)], 'notify' => 1])
            ->assertSessionHas('pf_warning');
        $this->assertSame(0, $product->files()->count());

        $product->update(['status' => 'published']);
        $this->actingAs($vendor)->withSession(['view_mode' => 'vendor'])
            ->post(route('vendor.products.files.store', $product), ['files' => [UploadedFile::fake()->create('a.exe', 1)], 'notify' => 1]);

        $this->assertSame(1, $product->files()->count());
        Mail::assertNothingQueued();
    }

    // ── Notification ─────────────────────────────────────────────

    public function test_notification_envoyee_aux_acheteurs_payes_et_attributions_actives(): void
    {
        Mail::fake();
        $product  = $this->makeProduct(['title' => 'Vidarod']);
        $paid     = $this->buyer($product);
        $pending  = $this->buyer($product, 'pending');
        $granted  = User::factory()->create();
        $this->grant($product, $granted);
        $revoked  = User::factory()->create();
        $this->grant($product, $revoked, revoked: true);
        $both     = $this->buyer($product);
        $this->grant($product, $both);
        $stranger = User::factory()->create();
        $this->buyer($this->makeProduct());   // acheteur d'un autre produit

        $this->adminUpload($product, [UploadedFile::fake()->create('Vidarod.dmg', 10)], [
            'labels' => ['macOS'], 'platforms' => ['macos'], 'notify' => 1,
        ])->assertSessionHas('pf_success');

        Mail::assertQueuedCount(3);
        foreach ([$paid, $granted, $both] as $user) {
            Mail::assertQueued(ProductFilesAddedMail::class, fn ($m) => $m->hasTo($user->email)
                && $m->product->is($product) && $m->fileLabels === ['macOS']);
        }
        foreach ([$pending, $revoked, $stranger] as $user) {
            Mail::assertNotQueued(ProductFilesAddedMail::class, fn ($m) => $m->hasTo($user->email));
        }
    }

    public function test_mail_contenu_sujet_lien_et_signature(): void
    {
        $product = $this->makeProduct(['title' => 'Vidarod', 'slug' => 'vidarod']);
        $mail    = new ProductFilesAddedMail($product, User::factory()->create(['name' => 'Awa']), ['macOS']);

        $mail->assertHasSubject('Nouvelle version disponible : Vidarod');
        $mail->assertSeeInHtml(route('marketplace.show', 'vidarod'));
        $mail->assertSeeInHtml("L'équipe Boursiv", false);
        $mail->assertSeeInText("L'équipe Boursiv");
        $this->assertInstanceOf(\Illuminate\Contracts\Queue\ShouldQueue::class, $mail);
    }

    public function test_pas_de_notification_sans_case_cochee_ni_pour_un_stagiaire(): void
    {
        Mail::fake();
        $product = $this->makeProduct();
        $this->buyer($product);

        $this->adminUpload($product, [UploadedFile::fake()->create('a.exe', 1)]);
        $this->flushSession();
        $this->adminUpload($product, [UploadedFile::fake()->create('b.exe', 1)], ['notify' => 1], ['is_stagiaire' => true])
            ->assertSessionHas('pf_success');

        $this->assertSame(2, $product->files()->count());
        Mail::assertNothingQueued();
    }

    // ── Pages ────────────────────────────────────────────────────

    public function test_page_produit_non_acheteur_affiche_les_plateformes_sans_lien(): void
    {
        $product = $this->makeProduct();
        $win = $this->storedFile($product);
        $this->storedFile($product, ['label' => 'Windows — Portable', 'platform' => 'windows_portable', 'sort_order' => 1]);
        $this->storedFile($product, ['label' => 'macOS', 'platform' => 'macos', 'sort_order' => 2]);

        $this->actingAs(User::factory()->create())->get(route('marketplace.show', $product->slug))
            ->assertOk()
            ->assertSee('Disponible pour')
            ->assertSee('Windows et macOS', false)
            ->assertSee('Payer maintenant')
            ->assertDontSee($this->downloadUrl($product, $win), false);
    }

    public function test_page_produit_acheteur_met_en_avant_la_version_de_son_os(): void
    {
        $product = $this->makeProduct();
        $mac = $this->storedFile($product, ['label' => 'macOS', 'platform' => 'macos', 'sort_order' => 0]);
        $win = $this->storedFile($product, ['label' => 'Windows — Installeur', 'platform' => 'windows', 'sort_order' => 1]);

        $response = $this->actingAs($this->buyer($product))
            ->withHeader('User-Agent', self::WINDOWS_UA)
            ->get(route('marketplace.show', $product->slug))
            ->assertOk()
            ->assertSee('Recommandé pour votre appareil')
            ->assertSee($this->downloadUrl($product, $mac), false)
            ->assertSee($this->downloadUrl($product, $win), false);

        // La version Windows passe devant macOS
        $html = $response->getContent();
        $this->assertLessThan(
            strpos($html, $this->downloadUrl($product, $mac)),
            strpos($html, $this->downloadUrl($product, $win))
        );
    }

    public function test_mes_produits_liste_les_fichiers(): void
    {
        $product = $this->makeProduct();
        $a = $this->storedFile($product);
        $b = $this->storedFile($product, ['label' => 'macOS', 'platform' => 'macos', 'sort_order' => 1]);

        $this->actingAs($this->buyer($product))->get(route('my.products'))
            ->assertOk()
            ->assertSee($this->downloadUrl($product, $a), false)
            ->assertSee($this->downloadUrl($product, $b), false);
    }

    public function test_pages_d_edition_affichent_le_gestionnaire(): void
    {
        $vendor  = $this->vendor();
        $product = $this->makeProduct(['user_id' => $vendor->id, 'status' => 'draft']);
        $this->storedFile($product);

        $this->withSession(['is_admin' => true])->get(route('admin.marketplace.edit', $product))
            ->assertOk()
            ->assertSee('Fichiers téléchargeables')
            ->assertSee('Prévenir les acheteurs par email')
            ->assertSee('un fichier par envoi', false);

        $this->actingAs($vendor)->withSession(['view_mode' => 'vendor'])->get(route('vendor.products.edit', $product))
            ->assertOk()
            ->assertSee('Fichiers téléchargeables')
            ->assertDontSee('Prévenir les acheteurs par email');
    }

    public function test_envoi_trop_volumineux_renvoie_au_formulaire_avec_un_message(): void
    {
        $product = $this->makeProduct();
        $edit    = route('admin.marketplace.edit', $product);

        $this->withSession(['is_admin' => true])
            ->call('POST', route('admin.marketplace.files.store', $product), [], [], [], [
                'CONTENT_LENGTH' => 50 * 1024 ** 3,
                'HTTP_REFERER'   => $edit,
            ])
            ->assertRedirect($edit . '?upload_trop_gros=1#fichiers');

        $this->withSession(['is_admin' => true])->get($edit . '?upload_trop_gros=1')
            ->assertSee('Envoi trop volumineux');
    }

    // ── Détection d'OS ───────────────────────────────────────────

    public function test_detection_os_depuis_le_user_agent(): void
    {
        $this->assertSame('windows', ProductFile::osFromUserAgent(self::WINDOWS_UA));
        $this->assertSame('macos', ProductFile::osFromUserAgent('Mozilla/5.0 (Macintosh; Intel Mac OS X 14_5) AppleWebKit/605.1.15'));
        $this->assertSame('android', ProductFile::osFromUserAgent('Mozilla/5.0 (Linux; Android 14; SM-A546B) AppleWebKit/537.36'));
        $this->assertSame('ios', ProductFile::osFromUserAgent('Mozilla/5.0 (iPhone; CPU iPhone OS 17_5 like Mac OS X)'));
        $this->assertSame('linux', ProductFile::osFromUserAgent('Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:131.0)'));
        $this->assertNull(ProductFile::osFromUserAgent(null));
    }
}
