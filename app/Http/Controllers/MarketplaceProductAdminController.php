<?php

namespace App\Http\Controllers;

use App\Models\MarketplaceProduct;
use App\Models\MarketplaceCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class MarketplaceProductAdminController extends Controller
{
    public function index(Request $request)
    {
        $q = MarketplaceProduct::query()
            ->with('category')
            ->latest();

        if ($request->filled('status')) {
            $q->where('status', (string) $request->input('status'));
        }

        if ($request->filled('type')) {
            $q->where('type', (string) $request->input('type'));
        }

        if ($request->filled('s')) {
            $s = trim((string) $request->input('s'));
            $q->where('title', 'like', "%{$s}%");
        }

        $products = $q->paginate(20)->withQueryString();

        $stats = [
            'total'     => MarketplaceProduct::count(),
            'published' => MarketplaceProduct::where('status', 'published')->count(),
            'draft'     => MarketplaceProduct::where('status', 'draft')->count(),
        ];

        return view('admin.marketplace.products.index', compact('products', 'stats'));
    }

    public function create()
    {
        $categories = MarketplaceCategory::orderBy('name')->get();
        return view('admin.marketplace.products.create', compact('categories'));
    }

    public function store(Request $request)
    {
        // 1) Validation de base
        $data = $request->validate([
            'category_id' => ['nullable', 'integer', 'exists:marketplace_categories,id'],
            'title'       => ['required', 'string', 'max:255'],
            'type'        => ['required', 'in:video,book,software'],
            'description' => ['nullable', 'string'],
            'price'       => ['required', 'integer', 'min:0'],
            'status'      => ['required', 'in:draft,published'],
            'is_featured' => ['nullable', 'boolean'],

            // cover image
            'cover'       => ['nullable', 'image', 'max:4096'],

            // fichier produit (PDF/ZIP/RAR) - conditionné ensuite
            'file'        => ['nullable', 'file', 'max:102400'], // 100MB max (ajuste)
        ], [], [
            'file' => 'fichier produit',
            'cover' => 'image de couverture',
        ]);

        // 2) Validation conditionnelle selon le type
        if ($data['type'] === 'book') {
            $request->validate([
                'file' => ['required', 'file', 'mimes:pdf', 'max:51200'], // 50MB
            ], [], ['file' => 'PDF du livre']);
        }

        if ($data['type'] === 'software') {
            $request->validate([
                'file' => ['required', 'file', 'mimes:zip,rar', 'max:102400'], // 100MB
            ], [], ['file' => 'archive du logiciel']);
        }

        // (video) : pour l’instant pas de fichier obligatoire.
        // plus tard on mettra un champ url.

        // 3) Slug unique
        $slug = Str::slug($data['title']);
        if (MarketplaceProduct::where('slug', $slug)->exists()) {
            $slug .= '-' . Str::random(6);
        }

        // 4) Upload cover
        $coverPath = null;
        if ($request->hasFile('cover')) {
            $coverPath = $request->file('cover')->store('marketplace/covers', 'public');
        }

        // 5) Création produit
        $product = MarketplaceProduct::create([
            'category_id'      => $data['category_id'] ?? null,
            'title'            => $data['title'],
            'slug'             => $slug,
            'type'             => $data['type'],
            'description'      => $data['description'] ?? null,
            'price'            => (int) $data['price'],
            'status'           => $data['status'],
            'is_featured'      => (bool) ($data['is_featured'] ?? false),
            'cover_image_path' => $coverPath,
        ]);

        // 6) Si fichier => créer asset (PDF/ZIP/RAR)
        if ($request->hasFile('file')) {
            $uploadedPath = $request->file('file')->store('marketplace/files', 'public');

            $label = match ($product->type) {
                'book'     => 'PDF',
                'software' => 'Logiciel',
                default    => 'Fichier',
            };

            $product->assets()->create([
                'kind'            => 'file',
                'path'            => $uploadedPath,
                'url'             => null,
                'label'           => $label,
                'is_downloadable' => true,
            ]);
        }

        return redirect()->route('admin.marketplace.index')->with('success', 'Produit créé ✅');
    }

    public function edit(MarketplaceProduct $product)
{
    $categories = MarketplaceCategory::orderBy('name')->get();
    $product->load('assets'); // important pour afficher le fichier actuel
    return view('admin.marketplace.products.edit', compact('product', 'categories'));
}

    public function update(Request $request, MarketplaceProduct $product)
{
    // 0) Charger les assets (utile pour déterminer s'il existe déjà un fichier)
    $product->load('assets');

    // 1) Validation de base
    $data = $request->validate([
        'category_id' => ['nullable', 'integer', 'exists:marketplace_categories,id'],
        'title'       => ['required', 'string', 'max:255'],
        'type'        => ['required', 'in:video,book,software'],
        'description' => ['nullable', 'string'],
        'price'       => ['required', 'integer', 'min:0'],
        'status'      => ['required', 'in:draft,published'],
        'is_featured' => ['nullable', 'boolean'],

        'cover'       => ['nullable', 'image', 'max:4096'],     // 4MB
        'file'        => ['nullable', 'file', 'max:102400'],    // 100MB
    ], [], [
        'file'  => 'fichier produit',
        'cover' => 'image de couverture',
    ]);

    // 2) Validation conditionnelle du fichier produit
    $hasFileAsset = $product->assets->firstWhere('kind', 'file') !== null;
    $requiresFile = in_array($data['type'], ['book', 'software'], true);

    // Si on est en book/software et qu'il n'y a pas encore de fichier -> il faut obliger un upload
    if ($requiresFile && !$hasFileAsset && !$request->hasFile('file')) {
        $request->validate([
            'file' => ['required'],
        ], [
            'file.required' => 'Le fichier produit est obligatoire pour ce type.',
        ], [
            'file' => 'fichier produit',
        ]);
    }

    // Si un fichier est uploadé, on vérifie les mimes selon type
    if ($request->hasFile('file')) {
        if ($data['type'] === 'book') {
            $request->validate([
                'file' => ['file', 'mimes:pdf', 'max:51200'], // 50MB
            ], [], ['file' => 'PDF du livre']);
        }

        if ($data['type'] === 'software') {
            $request->validate([
                'file' => ['file', 'mimes:zip,rar', 'max:102400'], // 100MB
            ], [], ['file' => 'archive du logiciel']);
        }

        // (video) : fichier non obligatoire, et pas de contrainte ici pour le moment.
    }

    // 3) Upload cover (si nouveau) -> on garde l'ancienne si rien n'est uploadé
    $coverPath = $product->cover_image_path;
    if ($request->hasFile('cover')) {
        $coverPath = $request->file('cover')->store('marketplace/covers', 'public');
    }

    // 4) Update produit (inclut cover)
    $product->update([
        'category_id'      => $data['category_id'] ?? null,
        'title'            => $data['title'],
        'type'             => $data['type'],
        'description'      => $data['description'] ?? null,
        'price'            => (int) $data['price'],
        'status'           => $data['status'],
        'is_featured'      => (bool) ($data['is_featured'] ?? false),
        'cover_image_path' => $coverPath,
    ]);

    // 5) Si nouveau fichier => remplacer / créer asset principal "file"
    if ($request->hasFile('file')) {
        $uploadedPath = $request->file('file')->store('marketplace/files', 'public');

        $label = match ($product->type) {
            'book'     => 'PDF',
            'software' => 'Logiciel',
            default    => 'Fichier',
        };

        $asset = $product->assets()->where('kind', 'file')->first();

        if ($asset) {
            $asset->update([
                'path'            => $uploadedPath,
                'url'             => null,
                'label'           => $label,
                'is_downloadable' => true,
            ]);
        } else {
            $product->assets()->create([
                'kind'            => 'file',
                'path'            => $uploadedPath,
                'url'             => null,
                'label'           => $label,
                'is_downloadable' => true,
            ]);
        }
    }

    return back()->with('success', 'Produit mis à jour ✅');
}

    public function destroy(MarketplaceProduct $product)
    {
        $product->delete();
        return back()->with('success', 'Produit supprimé ✅');
    }
}
