<?php

namespace App\Http\Controllers;

use App\Models\AdminGrant;
use App\Models\MarketplaceProduct;
use App\Models\MarketplaceCategory;
use Illuminate\Http\Request;
 use Illuminate\Support\Facades\Storage;

class MarketplaceController extends Controller
{

public function index(Request $request)
{
    $q = MarketplaceProduct::query()
        ->with('category')
        ->where('status', 'published')
        ->latest();

    if ($request->filled('type')) {
        $q->where('type', (string) $request->input('type'));
    }

    if ($request->filled('category')) {
        $q->where('category_id', (int) $request->input('category'));
    }

    if ($request->filled('s')) {
        $s = trim((string) $request->input('s'));
        $q->where('title', 'like', "%{$s}%");
    }

    $products = $q->paginate(12)->withQueryString();

    $categories = MarketplaceCategory::orderBy('name')->get();

    // IDs des produits accessibles (achetés OU grantés)
    $ownedIds = [];
    if (auth()->check()) {
        $user = auth()->user();

        $purchasedIds = $user->purchasedProducts()
            ->wherePivot('status', 'paid')
            ->pluck('marketplace_products.id');

        $grantedIds = AdminGrant::where('user_id', $user->id)
            ->where('grantable_type', MarketplaceProduct::class)
            ->active()
            ->pluck('grantable_id');

        $ownedIds = $purchasedIds->merge($grantedIds)->unique()->values()->all();
    }

    return view('marketplace.index', compact('products', 'categories', 'ownedIds'));
}






public function show(string $slug)
{
    $product = MarketplaceProduct::query()
        ->with(['category', 'assets'])
        ->where('status', 'published')
        ->where('slug', $slug)
        ->firstOrFail();

    // Est-ce que l'utilisateur a accès (acheté OU granté) ?
    $isOwned = false;
    if (auth()->check()) {
        $user = auth()->user();
        $isOwned = $user->purchasedProducts()
                ->where('marketplace_products.id', $product->id)
                ->wherePivot('status', 'paid')
                ->exists()
            || $product->isGrantedTo($user);
    }

    // ✅ URLs d'aperçu PDF (p1..p5) - seulement book et seulement si pas acheté
    // On vérifie l'existence des fichiers directement (indépendant de pages_count)
    $previewUrls = [];
    if ($product->type === 'book' && !$isOwned) {
        for ($i = 1; $i <= 5; $i++) {
            $path = "marketplace/previews/{$product->id}/p{$i}.jpg";
            if (Storage::disk('public')->exists($path)) {
                $previewUrls[] = asset('storage/' . $path);
            }
        }
    }

    // Produits similaires
    $related = MarketplaceProduct::query()
        ->with('category')
        ->where('status', 'published')
        ->where('id', '!=', $product->id)
        ->when($product->category_id, fn ($qq) => $qq->where('category_id', $product->category_id))
        ->latest()
        ->take(6)
        ->get();

    return view('marketplace.show', compact('product', 'related', 'isOwned', 'previewUrls'));
}

public function activation(MarketplaceProduct $product)
{
    // ✅ sécurité : user doit avoir payé ce produit
    $user = auth()->user();

    $isOwned = $user->purchasedProducts()
            ->where('marketplace_products.id', $product->id)
            ->wherePivot('status', 'paid')
            ->exists()
        || $product->isGrantedTo($user);

    abort_unless($isOwned, 403);

    // optionnel : whatsapp du support
    $wa = preg_replace('/[^0-9]/', '', (string) ($product->support_whatsapp ?? ''));
    $waText = rawurlencode("Bonjour, j'ai acheté \"{$product->title}\" sur Boursiv. Je veux activer ma licence.");

    return view('marketplace.activation', compact('product', 'wa', 'waText'));
}


//     public function show($slug)
// {
//     $product = MarketplaceProduct::with(['category','assets'])->where('slug', $slug)->firstOrFail();

//     dd([
//         'support_whatsapp' => $product->support_whatsapp,
//         'type' => $product->type,
//         'slug' => $product->slug,
//     ]);

//     return view('marketplace.show', compact('product'));
// }

}
