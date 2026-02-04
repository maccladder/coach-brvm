<?php

namespace App\Http\Controllers;

use App\Models\MarketplaceProduct;
use App\Models\MarketplaceCategory;
use Illuminate\Http\Request;

class MarketplaceController extends Controller
{
    public function index(Request $request)
    {
        $q = MarketplaceProduct::query()
            ->with('category')
            ->where('status', 'published')
            ->latest();

        if ($request->filled('type')) {
            $q->where('type', $request->string('type'));
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

        // ✅ IDs des produits déjà achetés (paid) par l'user connecté
        $ownedIds = [];
        if (auth()->check() && method_exists(auth()->user(), 'purchasedProducts')) {
            $ownedIds = auth()->user()
                ->purchasedProducts()
                ->wherePivot('status', 'paid')
                ->pluck('marketplace_products.id')
                ->all();
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

        // ✅ Est-ce que l'utilisateur a déjà acheté ?
        $isOwned = false;
        if (auth()->check() && method_exists(auth()->user(), 'purchasedProducts')) {
            $isOwned = auth()->user()
                ->purchasedProducts()
                ->where('marketplace_products.id', $product->id)
                ->wherePivot('status', 'paid')
                ->exists();
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

        return view('marketplace.show', compact('product', 'related', 'isOwned'));
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
