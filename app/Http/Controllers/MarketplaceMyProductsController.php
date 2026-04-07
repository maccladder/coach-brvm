<?php

namespace App\Http\Controllers;

use App\Models\GamePremiumUnlock;
use App\Models\MarketplaceProduct;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class MarketplaceMyProductsController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $query = $user->purchasedProducts()
            ->with('category')
            ->wherePivot('status', 'paid');

        if (request()->filled('type')) {
            $query->where('marketplace_products.type', request('type'));
        }

        $products = $query->latest('marketplace_purchases.created_at')->get();

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

    /**
     * Sert le HTML brut du jeu (utilisé comme src de l'iframe).
     * Vérifie l'achat avant de délivrer le contenu.
     */
    public function gameHtml(Request $request, MarketplaceProduct $product)
    {
        $user = $request->user();

        $hasAccess = $user->purchasedProducts()
            ->where('marketplace_products.id', $product->id)
            ->wherePivot('status', 'paid')
            ->exists();

        if (!$hasAccess) {
            abort(403);
        }

        if ($product->type !== 'game' || empty($product->game_html)) {
            abort(404);
        }

        // Remplace l'URL de Paystack inline.js par notre faux script proxy.
        // Le faux script intercepte PaystackPop.setup et délègue au parent (hors sandbox).
        $mockUrl = route('paystack.mock-inline');
        $html = str_replace(
            ['https://js.paystack.co/v1/inline.js', "https://js.paystack.co/v1/inline.js"],
            $mockUrl,
            $product->game_html
        );

        // Injecte le flag admin côté serveur — le placeholder __ADMIN_MODE__ n'apparaît
        // jamais dans le HTML servi, donc le secret n'est pas exposé aux joueurs.
        $isAdmin = session('is_admin') ? 'true' : 'false';
        $html = str_replace('__ADMIN_MODE__', $isAdmin, $html);

        return response($html, 200)
            ->header('Content-Type', 'text/html; charset=UTF-8')
            ->header('X-Frame-Options', 'SAMEORIGIN')
            ->header('Cache-Control', 'no-store');
    }

    public function play(Request $request, MarketplaceProduct $product)
    {
        $user = $request->user();

        $hasAccess = $user->purchasedProducts()
            ->where('marketplace_products.id', $product->id)
            ->wherePivot('status', 'paid')
            ->exists();

        if (!$hasAccess) {
            abort(403, "Accès refusé : produit non acheté.");
        }

        if ($product->type !== 'game') {
            abort(404, "Ce produit n'est pas un jeu.");
        }

        if (empty($product->game_html)) {
            abort(404, "Fichier de jeu introuvable.");
        }

        $isPremiumUnlocked = GamePremiumUnlock::where('user_id', $user->id)
            ->where('product_id', $product->id)
            ->where('feature', 'premium_chars')
            ->exists();

        return view('my-products-play', compact('product', 'isPremiumUnlocked'));
    }

    // visualiser une vidéo Cloudflare Stream
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

        // ✅ On récupère l'asset stream (url = video_id)
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
