<?php

namespace App\Http\Controllers;

use App\Models\MarketplaceProduct;
use App\Models\MarketplaceCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class MarketplaceProductAdminController extends Controller
{
    // Limites (en KB, car Laravel "max" = kilobytes)
    private const MAX_COVER_KB = 4096;     // 4 MB
    private const MAX_FILE_KB  = 409600;   // 400 MB
    private const MAX_BOOK_KB  = 51200;    // 50 MB

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
            'cover'       => ['nullable', 'image', 'max:' . self::MAX_COVER_KB],

            // fichier produit (PDF/ZIP/RAR) - conditionné ensuite
            'file'        => ['nullable', 'file', 'max:' . self::MAX_FILE_KB], // 400MB

            // vidéo (Cloudflare) - conditionné ensuite
            'cloudflare_video_id' => ['nullable', 'string', 'max:255'],
        ], [], [
            'file'  => 'fichier produit',
            'cover' => 'image de couverture',
        ]);

        // 2) Validation conditionnelle selon le type
        if ($data['type'] === 'book') {
            $request->validate([
                'file' => ['required', 'file', 'mimes:pdf', 'max:' . self::MAX_BOOK_KB], // 50MB
            ], [], ['file' => 'PDF du livre']);
        }

        if ($data['type'] === 'software') {
            $request->validate([
                'file' => ['required', 'file', 'mimes:zip,rar', 'max:' . self::MAX_FILE_KB], // 400MB
            ], [], ['file' => 'archive du logiciel']);
        }

        if ($data['type'] === 'video') {
            $request->validate([
                'cloudflare_video_id' => ['required', 'string', 'max:255'],
            ], [], [
                'cloudflare_video_id' => 'Cloudflare Video ID',
            ]);
        }

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

        // 6) Si vidéo => créer asset stream (Cloudflare)
        if ($product->type === 'video') {
            $product->assets()->create([
                'kind'            => 'stream',
                'path'            => null,
                'url'             => trim((string) $request->input('cloudflare_video_id')),
                'label'           => 'Vidéo',
                'is_downloadable' => false,
            ]);
        }

        // 7) Si fichier => créer asset (PDF/ZIP/RAR)
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
        $product->load('assets');
        return view('admin.marketplace.products.edit', compact('product', 'categories'));
    }

    public function update(Request $request, MarketplaceProduct $product)
    {
        // 0) Charger les assets
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

            'cover'       => ['nullable', 'image', 'max:' . self::MAX_COVER_KB], // 4MB
            'file'        => ['nullable', 'file', 'max:' . self::MAX_FILE_KB],  // 400MB

            // vidéo (Cloudflare) - conditionné ensuite
            'cloudflare_video_id' => ['nullable', 'string', 'max:255'],
        ], [], [
            'file'  => 'fichier produit',
            'cover' => 'image de couverture',
        ]);

        // 2) Validation conditionnelle du fichier produit (book/software)
        $hasFileAsset = $product->assets->firstWhere('kind', 'file') !== null;
        $requiresFile = in_array($data['type'], ['book', 'software'], true);

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
                    'file' => ['file', 'mimes:pdf', 'max:' . self::MAX_BOOK_KB], // 50MB
                ], [], ['file' => 'PDF du livre']);
            }

            if ($data['type'] === 'software') {
                $request->validate([
                    'file' => ['file', 'mimes:zip,rar', 'max:' . self::MAX_FILE_KB], // 400MB
                ], [], ['file' => 'archive du logiciel']);
            }
        }

        // Validation vidéo
        if ($data['type'] === 'video') {
            $request->validate([
                'cloudflare_video_id' => ['required', 'string', 'max:255'],
            ], [], [
                'cloudflare_video_id' => 'Cloudflare Video ID',
            ]);
        }

        // 3) Upload cover (si nouveau)
        $coverPath = $product->cover_image_path;
        if ($request->hasFile('cover')) {
            $coverPath = $request->file('cover')->store('marketplace/covers', 'public');
        }

        // 4) Update produit
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

        // 5) Si type = vidéo => créer / update asset stream, et (optionnel) supprimer asset file
        if ($product->type === 'video') {
            $videoId = trim((string) $request->input('cloudflare_video_id'));

            $streamAsset = $product->assets()->where('kind', 'stream')->first();

            if ($streamAsset) {
                $streamAsset->update([
                    'path'            => null,
                    'url'             => $videoId,
                    'label'           => 'Vidéo',
                    'is_downloadable' => false,
                ]);
            } else {
                $product->assets()->create([
                    'kind'            => 'stream',
                    'path'            => null,
                    'url'             => $videoId,
                    'label'           => 'Vidéo',
                    'is_downloadable' => false,
                ]);
            }

            // Propre : si on passe un produit en vidéo, on supprime l'ancien fichier téléchargeable
            $product->assets()->where('kind', 'file')->delete();

            return back()->with('success', 'Produit mis à jour ✅');
        }

        // 6) Si nouveau fichier => remplacer / créer asset principal "file"
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

        // Si on passe en book/software, on peut enlever un ancien asset stream (optionnel mais propre)
        $product->assets()->where('kind', 'stream')->delete();

        return back()->with('success', 'Produit mis à jour ✅');
    }

    public function destroy(MarketplaceProduct $product)
    {
        $product->delete();
        return back()->with('success', 'Produit supprimé ✅');
    }
}
