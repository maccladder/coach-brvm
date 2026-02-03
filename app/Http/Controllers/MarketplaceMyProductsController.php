<?php

namespace App\Http\Controllers;

use App\Models\MarketplaceProduct;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class MarketplaceMyProductsController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $products = $user->purchasedProducts()
            ->with('category')
            ->wherePivot('status', 'paid')
            ->latest('marketplace_purchases.created_at')
            ->get();

        return view('my-products', compact('products'));
    }

    public function download(Request $request, MarketplaceProduct $product)
    {
        $user = $request->user();

        $hasAccess = $user->purchasedProducts()
            ->where('marketplace_products.id', $product->id)
            ->wherePivot('status', 'paid')
            ->exists();

        if (!$hasAccess) {
            abort(403, "Accès refusé : produit non acheté.");
        }

        // ✅ pas de téléchargement pour les vidéos
        if ($product->type === 'video') {
            abort(403, "Téléchargement indisponible : ce produit est une vidéo.");
        }

        $asset = $product->assets()
            ->where('kind', 'file')
            ->where('is_downloadable', true)
            ->latest()
            ->first();

        if (!$asset || !$asset->path) {
            abort(404, "Fichier introuvable pour ce produit.");
        }

        $disk = 'public';

        if (!Storage::disk($disk)->exists($asset->path)) {
            abort(404, "Fichier introuvable (storage).");
        }

        $downloadName = $product->slug . '.' . pathinfo($asset->path, PATHINFO_EXTENSION);

        return Storage::disk($disk)->download($asset->path, $downloadName);
    }

    // ✅ NOUVEAU : visualiser une vidéo Cloudflare Stream
    public function watch(Request $request, MarketplaceProduct $product)
    {
        $user = $request->user();

        $hasAccess = $user->purchasedProducts()
            ->where('marketplace_products.id', $product->id)
            ->wherePivot('status', 'paid')
            ->exists();

        if (!$hasAccess) {
            abort(403, "Accès refusé : produit non acheté.");
        }

        if ($product->type !== 'video') {
            abort(404, "Ce produit n'est pas une vidéo.");
        }

        // ✅ On récupère l’asset stream (url = video_id)
        $streamAsset = $product->assets()
            ->where('kind', 'stream')
            ->latest()
            ->first();

        if (!$streamAsset || empty($streamAsset->url)) {
            abort(404, "Vidéo introuvable (Cloudflare Video ID manquant).");
        }

        $cloudflareVideoId = trim((string) $streamAsset->url);

        return view('my-products-watch', compact('product', 'cloudflareVideoId'));
    }
}
