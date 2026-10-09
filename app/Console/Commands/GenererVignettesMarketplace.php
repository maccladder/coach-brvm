<?php

namespace App\Console\Commands;

use App\Models\MarketplaceProduct;
use App\Services\Marketplace\VignetteService;
use Illuminate\Console\Command;

class GenererVignettesMarketplace extends Command
{
    protected $signature = 'marketplace:vignettes
                            {--tous : toutes les couvertures (par défaut : les jeux)}
                            {--force : régénérer les vignettes existantes}';

    protected $description = 'Génère les miniatures légères (400×300) des couvertures marketplace';

    public function handle(VignetteService $vignettes): int
    {
        $produits = MarketplaceProduct::query()
            ->whereNotNull('cover_image_path')
            ->when(!$this->option('tous'), fn ($q) => $q->where('type', 'game'))
            ->get(['id', 'title', 'cover_image_path']);

        $ok = 0;
        foreach ($produits as $p) {
            $chemin = $vignettes->generer($p->cover_image_path, (bool) $this->option('force'));

            if ($chemin) {
                $ok++;
                $this->line("✓ {$p->title} → {$chemin}");
            } else {
                $this->warn("✗ {$p->title} : couverture introuvable ou illisible ({$p->cover_image_path})");
            }
        }

        $this->info("{$ok} / {$produits->count()} vignette(s) prête(s).");

        return $ok === $produits->count() ? self::SUCCESS : self::FAILURE;
    }
}
