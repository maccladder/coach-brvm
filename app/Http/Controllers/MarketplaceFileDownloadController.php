<?php

namespace App\Http\Controllers;

use App\Models\MarketplaceProduct;
use App\Models\ProductFile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class MarketplaceFileDownloadController extends Controller
{
    /**
     * Téléchargement d'un fichier par l'acheteur. L'accès se vérifie au niveau du PRODUIT :
     * tout acheteur (ou bénéficiaire d'une attribution) a accès à tous ses fichiers,
     * y compris ceux ajoutés après son achat.
     */
    public function __invoke(Request $request, MarketplaceProduct $product, ProductFile $file)
    {
        abort_unless((int) $file->product_id === (int) $product->id, 404);
        abort_unless($product->isAccessibleBy($request->user()), 403, 'Accès refusé : produit non acheté.');

        $disk = Storage::disk($file->disk);
        abort_unless($disk->exists($file->path), 404, 'Fichier introuvable.');

        $file->increment('download_count');

        // StreamedResponse : le fichier n'est jamais chargé en mémoire
        return $disk->download($file->path, $file->original_name);
    }
}
