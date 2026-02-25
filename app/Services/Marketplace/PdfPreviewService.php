<?php

namespace App\Services\Marketplace;

use Illuminate\Support\Facades\Storage;
use Imagick;

class PdfPreviewService
{
    /**
     * Génère les previews et retourne le nombre total de pages.
     * $pdfPath = chemin SUR DISK 'public' (ex: marketplace/files/xxx.pdf)
     */
    public function generate(string $pdfPath, int $productId, int $maxPages = 5): int
    {
        if (!class_exists(Imagick::class)) {
            throw new \RuntimeException("Imagick non disponible sur ce serveur.");
        }

        $disk = Storage::disk('public');

        if (!$disk->exists($pdfPath)) {
            throw new \RuntimeException("PDF introuvable: {$pdfPath}");
        }

        $absolutePdf = $disk->path($pdfPath);

        // 1) compter pages (ping = léger)
        $ping = new \Imagick();
        $ping->pingImage($absolutePdf);
        $totalPages = (int) $ping->getNumberImages();
        $ping->clear();
        $ping->destroy();

        // 2) générer previews
        $previewDir = "marketplace/previews/{$productId}";
        $disk->makeDirectory($previewDir);

        $limit = min($maxPages, max(0, $totalPages));

        for ($i = 0; $i < $limit; $i++) {
            $img = new \Imagick();

            // meilleure lisibilité
            $img->setResolution(160, 160);
            $img->readImage($absolutePdf . '[' . $i . ']');
            $img->setImageFormat('jpeg');
            $img->setImageCompression(\Imagick::COMPRESSION_JPEG);
            $img->setImageCompressionQuality(82);

            // fond blanc si transparent
            $img->setImageBackgroundColor('white');
            $img = $img->mergeImageLayers(\Imagick::LAYERMETHOD_FLATTEN);

            // largeur max 900px
            $img->thumbnailImage(900, 900, true);

            $out = "{$previewDir}/p" . ($i + 1) . ".jpg";
            $img->writeImage($disk->path($out));

            $img->clear();
            $img->destroy();
        }

        return $totalPages;
    }

    public function previewUrls(int $productId, int $maxPages = 5): array
    {
        $disk = Storage::disk('public');
        $dir = "marketplace/previews/{$productId}";
        $urls = [];

        for ($i = 1; $i <= $maxPages; $i++) {
            $file = "{$dir}/p{$i}.jpg";
            if ($disk->exists($file)) {
                $urls[] = $disk->url($file);
            }
        }

        return $urls;
    }
}
