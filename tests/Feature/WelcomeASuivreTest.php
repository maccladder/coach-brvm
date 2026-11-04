<?php

namespace Tests\Feature;

use App\Models\BocStock;
use App\Models\DailyBoc;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Accueil : Akwaba dans le hero, cartes « À suivre » Sonatel / Bridge Bank
 * (dernier cours de clôture en base + variation sur la veille, prix d'introduction optionnel).
 */
class WelcomeASuivreTest extends TestCase
{
    use RefreshDatabase;

    private function cours(string $ticker, string $date, float $price, ?float $change): void
    {
        $daily = DailyBoc::query()->whereDate('date_boc', $date)->first()
            ?? DailyBoc::query()->create(['date_boc' => $date] + $this->dailyDefaults());

        BocStock::create([
            'daily_boc_id' => $daily->id, 'date_boc' => $date, 'ticker' => $ticker,
            'name' => $ticker, 'price' => $price, 'change' => $change,
        ]);
    }

    /** Colonnes obligatoires de daily_bocs, quelles qu'elles soient. */
    private function dailyDefaults(): array
    {
        $defaults = [];
        foreach (DB::select("PRAGMA table_info('daily_bocs')") as $col) {
            if ($col->notnull && $col->dflt_value === null && !$col->pk && !in_array($col->name, ['date_boc', 'created_at', 'updated_at'], true)) {
                $defaults[$col->name] = str_contains(strtolower($col->type), 'int') ? 0 : 'x';
            }
        }
        return $defaults;
    }

    private function accueil(): string
    {
        return $this->get(route('landing'))->assertOk()->getContent();
    }

    public function test_akwaba_est_dans_le_hero(): void
    {
        $html = $this->accueil();

        $hero = substr($html, strpos($html, 'class="cb-hero"'), strpos($html, 'À suivre à la BRVM') - strpos($html, 'class="cb-hero"'));
        $this->assertStringContainsString(route('akwaba.show'), $hero);
        $this->assertStringContainsString('img/jeux/akwaba/akwaba-hero.jpg', $hero);
        $this->assertStringContainsString('Akwaba : le mystère de la lagune Ébrié', $hero);
    }

    public function test_dernier_cours_variation_et_date(): void
    {
        $this->cours('SNTS', '2026-10-30', 28000, 1.2);
        $this->cours('SNTS', '2026-11-03', 28500, 1.79);   // le plus récent
        $this->cours('BBGC', '2026-11-03', 7100, -0.7);

        $html = $this->accueil();

        $this->assertStringContainsString('28 500 F CFA', $html);
        $this->assertStringContainsString('▲ +1,79 %', $html);
        $this->assertStringContainsString('7 100 F CFA', $html);
        $this->assertStringContainsString('▼ -0,70 %', $html);
        $this->assertStringContainsString('Clôture au 03/11', $html);
        $this->assertStringNotContainsString('28 000 F CFA', $html);
        $this->assertStringContainsString(route('societes.show', 'sonatel-senegal'), $html);
        $this->assertStringContainsString(route('societes.show', 'bridge-bank-ci'), $html);
    }

    public function test_variation_calculee_sur_la_veille_si_la_boc_ne_la_donne_pas(): void
    {
        $this->cours('SNTS', '2026-11-02', 20000, null);
        $this->cours('SNTS', '2026-11-03', 20500, null);

        $this->assertStringContainsString('▲ +2,50 %', $this->accueil());
    }

    public function test_sans_cours_en_base_cours_bientot_disponible(): void
    {
        $this->cours('SNTS', '2026-11-03', 28500, 0.5);

        $html = $this->accueil();

        $this->assertSame(1, substr_count($html, 'Cours bientôt disponible'));   // Bridge Bank seulement
        $this->assertStringContainsString('28 500 F CFA', $html);
    }

    public function test_prix_d_introduction_affiche_seulement_s_il_est_renseigne(): void
    {
        $this->assertNull(config('vitrine.societes.1.prix_introduction'));   // laissé vide
        $this->assertStringNotContainsString("Prix d'introduction", $this->accueil());

        config(['vitrine.societes.1.prix_introduction' => 6750]);
        $this->assertStringContainsString("Prix d'introduction : 6 750 F CFA", $this->accueil());
    }

    public function test_mention_educative_et_aucun_conseil(): void
    {
        $html = $this->accueil();

        $this->assertStringContainsString('Informations à titre éducatif, pas un conseil en investissement.', $html);

        foreach (config('vitrine.societes') as $s) {
            foreach (['acheter', 'achetez', 'vendre', 'vendez', 'conseil', 'opportunité', 'recommand'] as $mot) {
                $this->assertStringNotContainsStringIgnoringCase($mot, $s['accroche']);
            }
        }
    }
}
