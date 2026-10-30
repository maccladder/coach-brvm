<?php

namespace App\Console\Commands;

use App\Models\MarketplaceAsset;
use App\Models\ProductFile;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class MoveMarketplaceFilesPrivate extends Command
{
    protected $signature = 'marketplace:move-files-private
                            {--dry-run : Affiche ce qui serait déplacé sans rien modifier}';

    protected $description = 'Déplace les fichiers payants (marketplace_product_files) du disque public vers le disque privé.';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $source = Storage::disk('public');
        $target = config('marketplace.files_disk', 'local');
        $dest   = Storage::disk($target);

        $stats = ['moved' => 0, 'already' => 0, 'missing' => 0, 'errors' => 0];

        if ($dryRun) {
            $this->warn('Mode --dry-run : aucune modification.');
        }

        ProductFile::query()->orderBy('id')->chunkById(100, function ($files) use ($dryRun, $source, $dest, $target, &$stats) {
            foreach ($files as $file) {
                // refresh : une ligne partageant le même fichier a pu être déplacée juste avant
                if (!$dryRun) $file->refresh();

                if ($file->disk !== 'public') {
                    $stats['already']++;
                    continue;
                }

                $oldPath = $file->path;

                if (!$source->exists($oldPath)) {
                    $this->warn("⚠️  #{$file->id} manquant sur le disque public : {$oldPath}");
                    $stats['missing']++;
                    continue;
                }

                $size    = $source->size($oldPath);
                $newPath = "marketplace/product-files/{$file->product_id}/" . basename($oldPath);

                if ($dryRun) {
                    $this->line("→ #{$file->id} {$oldPath} → {$target}:{$newPath} (" . number_format($size, 0, ',', ' ') . ' octets)');
                    $stats['moved']++;
                    continue;
                }

                try {
                    // Copie en flux (gros fichiers), sauf si une copie complète existe déjà (relance)
                    if (!$dest->exists($newPath) || $dest->size($newPath) !== $size) {
                        $stream = $source->readStream($oldPath);
                        $dest->writeStream($newPath, $stream);
                        if (is_resource($stream)) fclose($stream);
                    }

                    if ($dest->size($newPath) !== $size) {
                        throw new \RuntimeException("taille différente après copie ({$dest->size($newPath)} ≠ {$size})");
                    }

                    // Toutes les références à ce fichier public passent sur le disque privé
                    DB::transaction(function () use ($oldPath, $newPath, $target) {
                        ProductFile::where('disk', 'public')->where('path', $oldPath)
                            ->update(['disk' => $target, 'path' => $newPath]);
                        MarketplaceAsset::where('disk', 'public')->where('path', $oldPath)
                            ->update(['disk' => $target, 'path' => $newPath]);
                    });

                    // Original supprimé seulement après copie vérifiée et références mises à jour
                    $source->delete($oldPath);

                    $this->info("✅ #{$file->id} {$oldPath} → {$target}:{$newPath}");
                    $stats['moved']++;
                } catch (\Throwable $e) {
                    $this->error("❌ #{$file->id} {$oldPath} : " . $e->getMessage());
                    $stats['errors']++;
                }
            }
        });

        $this->newLine();
        $this->table(
            ['Résumé', 'Nombre'],
            [
                [$dryRun ? 'À déplacer' : 'Déplacés', $stats['moved']],
                ['Déjà privés', $stats['already']],
                ['Manquants', $stats['missing']],
                ['Erreurs', $stats['errors']],
            ]
        );

        return $stats['errors'] > 0 ? self::FAILURE : self::SUCCESS;
    }
}
