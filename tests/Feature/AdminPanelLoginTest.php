<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Codes des panels admin et stagiaire : lus dans .env via la config, jamais dans le dépôt
 * (public). Les anciens codes écrits en dur ne fonctionnent plus.
 */
class AdminPanelLoginTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config([
            'services.admin_panel.admin_code'     => 'admin-test-9f3k',
            'services.admin_panel.stagiaire_code' => 'stagiaire-test-2m7q',
        ]);
    }

    public function test_le_bon_code_admin_ouvre_le_panel(): void
    {
        $this->post(route('admin.login'), ['code' => 'admin-test-9f3k'])
            ->assertRedirect(route('admin.dashboard'))
            ->assertSessionHas('is_admin', true);
    }

    public function test_les_anciens_codes_ecrits_dans_le_depot_sont_refuses(): void
    {
        $this->post(route('admin.login'), ['code' => 'Coach-brvm2025'])->assertSessionMissing('is_admin');
        $this->post(route('stagiaire.login'), ['code' => 'Stagiaire-brvm2025'])->assertSessionMissing('is_stagiaire');
    }

    public function test_un_mauvais_code_est_refuse(): void
    {
        $this->post(route('admin.login'), ['code' => 'admin-test-9f3K'])
            ->assertSessionHas('error', 'Code incorrect.')
            ->assertSessionMissing('is_admin');
    }

    public function test_sans_code_configure_l_acces_est_ferme(): void
    {
        config(['services.admin_panel.admin_code' => null, 'services.admin_panel.stagiaire_code' => '']);

        $this->post(route('admin.login'), ['code' => ''])->assertSessionMissing('is_admin');
        $this->post(route('admin.login'), ['code' => 'nimporte'])->assertSessionMissing('is_admin');
        $this->post(route('stagiaire.login'), ['code' => 'nimporte'])->assertSessionMissing('is_stagiaire');
    }

    public function test_le_code_stagiaire_ne_donne_pas_le_code_admin(): void
    {
        $this->post(route('stagiaire.login'), ['code' => 'stagiaire-test-2m7q'])
            ->assertSessionHas('is_stagiaire', true)
            ->assertSessionMissing('is_admin');

        $this->post(route('admin.login'), ['code' => 'stagiaire-test-2m7q'])->assertSessionMissing('is_admin');
    }

    public function test_les_tentatives_sont_limitees(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->post(route('admin.login'), ['code' => 'essai-' . $i])->assertRedirect();
        }

        $this->post(route('admin.login'), ['code' => 'admin-test-9f3k'])->assertStatus(429);
    }
}
