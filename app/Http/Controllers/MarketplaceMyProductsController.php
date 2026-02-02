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

        // On récupère le 1er asset fichier téléchargeable
        $asset = $product->assets()
            ->where('kind', 'file')
            ->where('is_downloadable', true)
            ->latest()
            ->first();

        if (!$asset || !$asset->path) {
            abort(404, "Fichier introuvable pour ce produit.");
        }

        // IMPORTANT :
        // si tu stockes sur disk 'public' => Storage::disk('public')
        // si tu veux + safe plus tard => disk 'private'
        $disk = 'public';

        if (!Storage::disk($disk)->exists($asset->path)) {
            abort(404, "Fichier introuvable (storage).");
        }

        $downloadName = $product->slug . '.' . pathinfo($asset->path, PATHINFO_EXTENSION);

        return Storage::disk($disk)->download($asset->path, $downloadName);
    }
}
