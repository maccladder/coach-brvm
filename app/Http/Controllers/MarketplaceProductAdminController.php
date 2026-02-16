<?php

namespace App\Http\Controllers;

use App\Models\MarketplaceAsset;
use App\Models\MarketplaceCategory;
use App\Models\MarketplaceProduct;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class MarketplaceProductAdminController extends Controller
{
    // Limites (en KB, car Laravel "max" = kilobytes)
    private const MAX_COVER_KB = 4096;     // 4 MB
    private const MAX_FILE_KB  = 409600;   // 400 MB
    private const MAX_BOOK_KB  = 51200;    // 50 MB

    /**
     * Nettoie un numéro WhatsApp : garde seulement les chiffres
     * Ex: +225 07 88 03 54 32 => 2250788035432
     */
    private function cleanWhatsapp(?string $value): ?string
    {
        $raw = trim((string) $value);
        if ($raw === '') {
            return null;
        }

        $digits = preg_replace('/[^0-9]/', '', $raw);
        return $digits ?: null;
    }

    public function index(Request $request)
    {
        $q = MarketplaceProduct::query()
            ->with(['category'])
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
            'pending'   => MarketplaceProduct::where('status', 'pending')->count(),
            'rejected'  => MarketplaceProduct::where('status', 'rejected')->count(),
        ];

        return view('admin.marketplace.products.index', compact('products', 'stats'));
    }

    public function show(MarketplaceProduct $product)
    {
        $product->load(['category', 'assets', 'vendor']); // vendor relation si tu l’as
        return view('admin.marketplace.products.show', compact('product'));
    }

    public function downloadAsset(MarketplaceAsset $asset)
    {
        // admin peut télécharger même si pas acheté
        $disk = 'public';

        abort_unless($asset->path && Storage::disk($disk)->exists($asset->path), 404);

        $filename = $asset->label
            ? Str::slug($asset->label) . '-' . basename($asset->path)
            : basename($asset->path);

        return Storage::disk($disk)->download($asset->path, $filename);
    }

    public function create()
    {
        $categories = MarketplaceCategory::orderBy('name')->get();
        return view('admin.marketplace.products.create', compact('categories'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'category_id' => ['nullable', 'integer', 'exists:marketplace_categories,id'],
            'title'       => ['required', 'string', 'max:255'],
            'type'        => ['required', 'in:video,book,software'],
            'description' => ['nullable', 'string'],
            'price'       => ['required', 'integer', 'min:0'],
            'status'      => ['required', 'in:draft,published,pending,rejected'],
            'is_featured' => ['nullable', 'boolean'],

            'support_whatsapp' => ['nullable', 'string', 'max:32'],

            'cover'       => ['nullable', 'image', 'max:' . self::MAX_COVER_KB],
            'file'        => ['nullable', 'file', 'max:' . self::MAX_FILE_KB],

            'cloudflare_video_id' => ['nullable', 'string', 'max:255'],
        ], [], [
            'file'  => 'fichier produit',
            'cover' => 'image de couverture',
        ]);

        $data['support_whatsapp'] = $this->cleanWhatsapp($data['support_whatsapp'] ?? null);

        if ($data['type'] === 'book') {
            $request->validate([
                'file' => ['required', 'file', 'mimes:pdf', 'max:' . self::MAX_BOOK_KB],
            ], [], ['file' => 'PDF du livre']);
        }

        if ($data['type'] === 'software') {
            $request->validate([
                'file' => ['required', 'file', 'mimes:zip,rar', 'max:' . self::MAX_FILE_KB],
            ], [], ['file' => 'archive du logiciel']);
        }

        if ($data['type'] === 'video') {
            $request->validate([
                'cloudflare_video_id' => ['required', 'string', 'max:255'],
            ], [], [
                'cloudflare_video_id' => 'Cloudflare Video ID',
            ]);
        }

        $slug = Str::slug($data['title']);
        if (MarketplaceProduct::where('slug', $slug)->exists()) {
            $slug .= '-' . Str::random(6);
        }

        $coverPath = null;
        if ($request->hasFile('cover')) {
            $coverPath = $request->file('cover')->store('marketplace/covers', 'public');
        }

        $product = MarketplaceProduct::create([
            'category_id'      => $data['category_id'] ?? null,
            'title'            => $data['title'],
            'slug'             => $slug,
            'type'             => $data['type'],
            'description'      => $data['description'] ?? null,
            'support_whatsapp' => $data['support_whatsapp'],
            'price'            => (int) $data['price'],
            'status'           => $data['status'],
            'is_featured'      => (bool) ($data['is_featured'] ?? false),
            'cover_image_path' => $coverPath,
        ]);

        if ($product->type === 'video') {
            $product->assets()->create([
                'kind'            => 'stream',
                'path'            => null,
                'url'             => trim((string) $request->input('cloudflare_video_id')),
                'label'           => 'Vidéo',
                'is_downloadable' => false,
            ]);
        }

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
        $product->load('assets');

        $data = $request->validate([
            'category_id' => ['nullable', 'integer', 'exists:marketplace_categories,id'],
            'title'       => ['required', 'string', 'max:255'],
            'type'        => ['required', 'in:video,book,software'],
            'description' => ['nullable', 'string'],
            'price'       => ['required', 'integer', 'min:0'],
            'status'      => ['required', 'in:draft,published,pending,rejected'],
            'is_featured' => ['nullable', 'boolean'],

            'support_whatsapp' => ['nullable', 'string', 'max:32'],

            'cover'       => ['nullable', 'image', 'max:' . self::MAX_COVER_KB],
            'file'        => ['nullable', 'file', 'max:' . self::MAX_FILE_KB],

            'cloudflare_video_id' => ['nullable', 'string', 'max:255'],
        ], [], [
            'file'  => 'fichier produit',
            'cover' => 'image de couverture',
        ]);

        $data['support_whatsapp'] = $this->cleanWhatsapp($data['support_whatsapp'] ?? null);

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

        if ($request->hasFile('file')) {
            if ($data['type'] === 'book') {
                $request->validate([
                    'file' => ['file', 'mimes:pdf', 'max:' . self::MAX_BOOK_KB],
                ], [], ['file' => 'PDF du livre']);
            }

            if ($data['type'] === 'software') {
                $request->validate([
                    'file' => ['file', 'mimes:zip,rar', 'max:' . self::MAX_FILE_KB],
                ], [], ['file' => 'archive du logiciel']);
            }
        }

        if ($data['type'] === 'video') {
            $request->validate([
                'cloudflare_video_id' => ['required', 'string', 'max:255'],
            ], [], [
                'cloudflare_video_id' => 'Cloudflare Video ID',
            ]);
        }

        $coverPath = $product->cover_image_path;
        if ($request->hasFile('cover')) {
            $coverPath = $request->file('cover')->store('marketplace/covers', 'public');
        }

        $product->update([
            'category_id'      => $data['category_id'] ?? null,
            'title'            => $data['title'],
            'type'             => $data['type'],
            'description'      => $data['description'] ?? null,
            'support_whatsapp' => $data['support_whatsapp'],
            'price'            => (int) $data['price'],
            'status'           => $data['status'],
            'is_featured'      => (bool) ($data['is_featured'] ?? false),
            'cover_image_path' => $coverPath,
        ]);

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

            $product->assets()->where('kind', 'file')->delete();

            return back()->with('success', 'Produit mis à jour ✅');
        }

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

        $product->assets()->where('kind', 'stream')->delete();

        return back()->with('success', 'Produit mis à jour ✅');
    }

    public function destroy(MarketplaceProduct $product)
    {
        $product->delete();
        return back()->with('success', 'Produit supprimé ✅');
    }

    public function approve(Request $request, MarketplaceProduct $product)
    {
        if ($product->status !== 'pending') {
            return back()->with('warning', 'Ce produit n’est pas en attente.');
        }

        // ✅ sécurité : on ne publie pas sans asset selon type
        $product->load('assets');

        if ($product->type === 'video') {
            if (!$product->assets->firstWhere('kind', 'stream')) {
                return back()->with('warning', 'Impossible d’approuver: aucune vidéo Cloudflare attachée.');
            }
        } else {
            if (!$product->assets->firstWhere('kind', 'file')) {
                return back()->with('warning', 'Impossible d’approuver: aucun fichier attaché (PDF/ZIP).');
            }
        }

        $product->status = 'published';
        $product->reviewed_at = now();
        $product->admin_note = null;
        $product->save();

        return back()->with('success', '✅ Produit approuvé et publié sur la marketplace.');
    }

    public function reject(Request $request, MarketplaceProduct $product)
    {
        if ($product->status !== 'pending') {
            return back()->with('warning', 'Ce produit n’est pas en attente.');
        }

        $data = $request->validate([
            'admin_note' => ['required','string','max:500'],
        ]);

        $product->status = 'rejected';
        $product->reviewed_at = now();
        $product->admin_note = $data['admin_note'];
        $product->save();

        return back()->with('success', '⛔ Produit rejeté. Le vendeur verra le motif.');
    }
}
