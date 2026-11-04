<?php

namespace Tests\Feature;

use App\Models\Analysis;
use App\Models\FinancialStatement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * /uploads (analyses et états financiers) : outil interne qui écrit sur le disque public.
 * Il était ouvert à tous, sans contrôle du type : un .php ou un .html pouvait être déposé
 * sous /storage. Désormais : code admin obligatoire et PDF uniquement.
 */
class UploadsSecurityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
    }

    public function test_les_uploads_sont_refuses_sans_code_admin(): void
    {
        foreach ([null, User::factory()->create()] as $who) {
            if ($who) {
                $this->actingAs($who);
            }
            $this->get(route('uploads.index'))->assertRedirect(route('admin.login.form'));
            $this->post(route('uploads.analysis.store'), [
                'as_of_date' => '2026-11-04', 'title' => 'x', 'file' => UploadedFile::fake()->create('a.pdf', 10, 'application/pdf'),
            ])->assertRedirect(route('admin.login.form'));
            $this->post(route('uploads.statement.store'), [
                'issuer' => 'SONATEL', 'period' => '2025', 'statement_type' => 'income', 'published_at' => '2026-03-01',
                'file' => UploadedFile::fake()->create('s.pdf', 10, 'application/pdf'),
            ])->assertRedirect(route('admin.login.form'));
        }

        $this->assertSame(0, Analysis::count());
        $this->assertSame(0, FinancialStatement::count());
        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    public function test_un_fichier_php_ou_html_est_refuse_meme_pour_l_admin(): void
    {
        $admin = $this->withSession(['is_admin' => true]);

        foreach ([['shell.php', 'text/x-php', '<?php system($_GET["c"]);'], ['page.html', 'text/html', '<script>alert(1)</script>']] as [$name, $mime, $content]) {
            $admin->post(route('uploads.statement.store'), [
                'issuer' => 'X', 'period' => '2025', 'statement_type' => 'income', 'published_at' => '2026-03-01',
                'file' => UploadedFile::fake()->createWithContent($name, $content)->mimeType($mime),
            ])->assertSessionHasErrors('file');
            $admin->post(route('uploads.analysis.store'), [
                'as_of_date' => '2026-11-04', 'title' => 'x',
                'file' => UploadedFile::fake()->createWithContent($name, $content)->mimeType($mime),
            ])->assertSessionHasErrors('file');
        }

        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    public function test_l_admin_peut_encore_envoyer_un_pdf(): void
    {
        $this->withSession(['is_admin' => true])->get(route('uploads.index'))->assertOk();

        $this->withSession(['is_admin' => true])->post(route('uploads.statement.store'), [
            'issuer' => 'SONATEL', 'period' => '2025', 'statement_type' => 'income', 'published_at' => '2026-03-01',
            'file' => UploadedFile::fake()->create('etats.pdf', 50, 'application/pdf'),
        ])->assertSessionHasNoErrors();

        $this->assertSame(1, FinancialStatement::count());
        $this->assertCount(1, Storage::disk('public')->files('statements'));
    }
}
