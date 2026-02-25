<?php

namespace App\Console\Commands;

use App\Models\MarketplaceProduct;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class MarketplaceBackfillPdfMeta extends Command
{
    protected $signature = 'marketplace:backfill-pdf
                            {--only-missing : Traiter seulement ceux dont pages_count est null}
                            {--limit=0 : Limiter le nombre (0 = pas de limite)}
                            {--previews=5 : Nombre de pages preview à générer}
                            {--force : Regénérer previews même si déjà présents}';

    protected $description = 'Backfill pages_count + générer previews pour les produits PDF (type=book) déjà uploadés.';

    public function handle(): int
    {
        if (!extension_loaded('imagick')) {
            $this->error('❌ Imagick n’est pas chargé (extension PHP).');
            return self::FAILURE;
        }

        $disk = 'public';

        $q = MarketplaceProduct::query()
            ->with('assets')
            ->where('type', 'book');

        if ($this->option('only-missing')) {
            $q->whereNull('pages_count');
        }

        $limit = (int) $this->option('limit');
        if ($limit > 0) $q->limit($limit);

        $previews = max(0, (int) $this->option('previews'));
        $force = (bool) $this->option('force');

        $products = $q->get();
        if ($products->isEmpty()) {
            $this->info('✅ Aucun produit PDF à traiter.');
            return self::SUCCESS;
        }

        $this->info("📚 Produits trouvés : {$products->count()}");

        $done = 0;
        $skipped = 0;
        $failed = 0;

        foreach ($products as $product) {
            $fileAsset = $product->assets->firstWhere('kind', 'file');

            if (!$fileAsset || empty($fileAsset->path)) {
                $this->warn("⚠️ #{$product->id} {$product->title} — aucun asset file.");
                $skipped++;
                continue;
            }

            if (!Storage::disk($disk)->exists($fileAsset->path)) {
                $this->warn("⚠️ #{$product->id} {$product->title} — fichier introuvable: {$fileAsset->path}");
                $failed++;
                continue;
            }

            $absolutePdf = Storage::disk($disk)->path($fileAsset->path);

            try {
                // 1) pages_count
                $im = new \Imagick();
                $im->pingImage($absolutePdf);
                $count = (int) $im->getNumberImages();
                $im->clear();
                $im->destroy();

                if ($count > 0) {
                    $product->pages_count = $count;
                    $product->save();
                }

                // 2) previews (p1.jpg .. pN.jpg)
                if ($previews > 0) {
                    $dir = "marketplace/previews/{$product->id}";
                    Storage::disk($disk)->makeDirectory($dir);

                    $maxPages = min($previews, max(1, $count));

                    for ($i = 1; $i <= $maxPages; $i++) {
                        // ✅ format demandé: p1.jpg, p2.jpg...
                        $out = "{$dir}/p{$i}.jpg";

                        if (!$force && Storage::disk($disk)->exists($out)) {
                            continue;
                        }

                        $pageIndex = $i - 1; // Imagick = 0-based

                        $img = new \Imagick();
                        $img->setResolution(150, 150);
                        $img->readImage($absolutePdf . "[{$pageIndex}]");
                        $img->setImageFormat('jpeg');
                        $img->setImageCompressionQuality(82);
                        $img->stripImage();

                        // ✅ limiter largeur (optionnel)
                        $width = $img->getImageWidth();
                        if ($width > 1200) {
                            $img->resizeImage(1200, 0, \Imagick::FILTER_LANCZOS, 1);
                        }

                        Storage::disk($disk)->put($out, $img->getImageBlob());

                        $img->clear();
                        $img->destroy();
                    }
                }

                $this->info("✅ #{$product->id} pages={$product->pages_count}");
                $done++;
            } catch (\Throwable $e) {
                $this->error("❌ #{$product->id} {$product->title} — " . $e->getMessage());
                $failed++;
            }
        }

        $this->line("");
        $this->info("Terminé ✅ done={$done} skipped={$skipped} failed={$failed}");

        return self::SUCCESS;
    }
}
