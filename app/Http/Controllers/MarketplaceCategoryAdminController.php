<?php

namespace App\Http\Controllers;

use App\Models\MarketplaceCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class MarketplaceCategoryAdminController extends Controller
{
    public function index()
    {
        $categories = MarketplaceCategory::orderBy('name')->paginate(30);
        return view('admin.marketplace.categories.index', compact('categories'));
    }

    public function create()
    {
        return view('admin.marketplace.categories.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required','string','max:255'],
        ]);

        $slug = Str::slug($data['name']);
        if (MarketplaceCategory::where('slug', $slug)->exists()) {
            $slug .= '-' . Str::random(6);
        }

        MarketplaceCategory::create([
            'name' => $data['name'],
            'slug' => $slug,
        ]);

        return redirect()->route('admin.marketplace-categories.index')
            ->with('success', 'Catégorie créée ✅');
    }

    public function edit(MarketplaceCategory $marketplace_category)
    {
        return view('admin.marketplace.categories.edit', [
            'category' => $marketplace_category
        ]);
    }

    public function update(Request $request, MarketplaceCategory $marketplace_category)
    {
        $data = $request->validate([
            'name' => ['required','string','max:255'],
        ]);

        $marketplace_category->update([
            'name' => $data['name'],
        ]);

        return back()->with('success', 'Catégorie mise à jour ✅');
    }

    public function destroy(MarketplaceCategory $marketplace_category)
    {
        if (!session('is_admin')) {
            abort(403, 'Action réservée aux administrateurs.');
        }

        $marketplace_category->delete();
        return back()->with('success', 'Catégorie supprimée ✅');
    }
}
