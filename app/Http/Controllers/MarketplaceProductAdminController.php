<?php

namespace App\Http\Controllers;

use App\Models\AdminNotification;
use App\Models\MarketplaceAsset;
use App\Models\MarketplaceCategory;
use App\Models\MarketplaceProduct;
use App\Models\User;
use App\Notifications\VendorProductReviewedNotification;
use App\Notifications\UserNewMarketplaceContentNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class MarketplaceProductAdminController extends Controller
{
    private const MAX_COVER_KB = 4096;     // 4 MB
    private const MAX_FILE_KB  = 409600;   // 400 MB
    private const MAX_BOOK_KB  = 51200;    // 50 MB

    private function cleanWhatsapp(?string $value): ?string
    {
        $raw = trim((string) $value);
        if ($raw === '') return null;

        $digits = preg_replace('/[^0-9]/', '', $raw);
        return $digits ?: null;
    }

    private function getVendor(MarketplaceProduct $product): ?User
    {
        if (method_exists($product, 'vendor')) {
            $product->loadMissing('vendor');
            return $product->vendor;
        }

        if (!empty($product->user_id)) {
            return User::find($product->user_id);
        }

        return null;
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
        $product->load(['category', 'assets', 'vendor']);
        return view('admin.marketplace.products.show', compact('product'));
    }

    public function downloadAsset(MarketplaceAsset $asset)
    {
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
            ], [], ['cloudflare_video_id' => 'Cloudflare Video ID']);
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
            $request->validate(['file' => ['required']], [
                'file.required' => 'Le fichier produit est obligatoire pour ce type.',
            ], ['file' => 'fichier produit']);
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
            ], [], ['cloudflare_video_id' => 'Cloudflare Video ID']);
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
                    'path' => null, 'url' => $videoId, 'label' => 'Vidéo', 'is_downloadable' => false,
                ]);
            } else {
                $product->assets()->create([
                    'kind' => 'stream', 'path' => null, 'url' => $videoId, 'label' => 'Vidéo', 'is_downloadable' => false,
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
                    'path' => $uploadedPath, 'url' => null, 'label' => $label, 'is_downloadable' => true,
                ]);
            } else {
                $product->assets()->create([
                    'kind' => 'file', 'path' => $uploadedPath, 'url' => null, 'label' => $label, 'is_downloadable' => true,
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

    $product->load(['assets', 'vendor']);

    // ✅ Vidéo => workflow spécial : page "pré-approuver" (Cloudflare)
    if ($product->type === 'video') {
        return redirect()->route('admin.marketplace.publish.form', $product);
    }

    // ✅ Sécurité PDF/ZIP : on exige un fichier
    if (!$product->assets->firstWhere('kind', 'file')) {
        return back()->with('warning', 'Impossible d’approuver: aucun fichier attaché (PDF/ZIP).');
    }

    // ✅ Publication (PDF/ZIP)
    $product->status      = 'published';
    $product->reviewed_at = now();
    $product->admin_note  = null;
    $product->save();

    $publicUrl = $product->slug
        ? route('marketplace.show', $product->slug)
        : route('marketplace.index');

    // 1) ✅ Notif vendeur
    $vendor = $product->vendor ?: $this->getVendor($product);
    if ($vendor) {
        $vendor->notify(new \App\Notifications\VendorProductReviewedNotification(
            productId: $product->id,
            productTitle: $product->title,
            status: 'approved',
            message: "🎉 Félicitations ! Ton produit « {$product->title} » a été approuvé par l’admin et est maintenant visible sur la marketplace.",
            url: $publicUrl
        ));
    }

    // 2) ✅ Notif autres utilisateurs
    \App\Models\User::query()
        ->where('id', '!=', $product->user_id)
        ->chunkById(500, function ($users) use ($product, $publicUrl) {
            foreach ($users as $u) {
                $u->notify(new \App\Notifications\UserNewMarketplaceContentNotification(
                    productId: $product->id,
                    productTitle: $product->title,
                    url: $publicUrl
                ));
            }
        });

    // 3) ✅ Notif admin (optionnel)
    if (class_exists(\App\Models\AdminNotification::class)) {
        \App\Models\AdminNotification::create([
            'type'    => 'product_approved',
            'title'   => 'Produit approuvé',
            'message' => $product->title,
            'url'     => route('admin.marketplace.show', $product),
            'read_at' => null,
        ]);
    }

    return back()->with('success', '✅ Produit approuvé et publié sur la marketplace.');
}



    public function reject(Request $request, MarketplaceProduct $product)
{
    if ($product->status !== 'pending') {
        return back()->with('warning', 'Ce produit n’est pas en attente.');
    }

    $data = $request->validate([
        'admin_note' => ['required', 'string', 'max:500'],
    ]);

    $product->load(['vendor']);

    // ✅ Rejet
    $product->status      = 'rejected';
    $product->reviewed_at = now();
    $product->admin_note  = $data['admin_note'];
    $product->save();

    // ✅ Notif vendeur (spéciale)
    $vendor = $product->vendor ?: $this->getVendor($product);

    if ($vendor) {
        $vendor->notify(new \App\Notifications\VendorProductReviewedNotification(
            productId: $product->id,
            productTitle: $product->title,
            status: 'rejected',
            message: "⛔ Ton produit « {$product->title} » a été rejeté par l’admin.\nMotif : {$data['admin_note']}\n👉 Corrige puis soumets à nouveau.",
            url: route('vendor.products.edit', $product)
        ));
    }

    // ✅ Notif admin (optionnel)
    if (class_exists(\App\Models\AdminNotification::class)) {
        \App\Models\AdminNotification::create([
            'type'    => 'product_rejected',
            'title'   => 'Produit rejeté',
            'message' => $product->title,
            'url'     => route('admin.marketplace.show', $product),
            'read_at' => null,
        ]);
    }

    return back()->with('success', '⛔ Produit rejeté. Le vendeur a été notifié avec le motif.');
}

public function publishForm(MarketplaceProduct $product)
{
    $product->load(['assets', 'vendor', 'category']);

    if ($product->type !== 'video') {
        return redirect()->route('admin.marketplace.show', $product)
            ->with('warning', 'Cette page est uniquement pour les vidéos.');
    }

    if ($product->status !== 'pending') {
        return redirect()->route('admin.marketplace.show', $product)
            ->with('warning', 'Ce produit n’est pas en attente.');
    }

    // ✅ il faut au moins le mp4 du vendeur (asset file)
    $hasFile = (bool) $product->assets->firstWhere('kind', 'file');
    if (!$hasFile) {
        return redirect()->route('admin.marketplace.show', $product)
            ->with('warning', 'Aucun fichier vidéo MP4 envoyé par le vendeur.');
    }

    // stream déjà présent ?
    $stream = $product->assets->firstWhere('kind', 'stream');

    return view('admin.marketplace.products.publish', compact('product', 'stream'));
}

public function publish(Request $request, MarketplaceProduct $product)
{
    $product->load(['assets', 'vendor']);

    if ($product->type !== 'video') {
        return back()->with('warning', 'Action réservée aux vidéos.');
    }

    if ($product->status !== 'pending') {
        return back()->with('warning', 'Ce produit n’est pas en attente.');
    }

    // ✅ doit avoir le mp4 vendeur pour inspection
    $hasFile = (bool) $product->assets->firstWhere('kind', 'file');
    if (!$hasFile) {
        return back()->with('warning', 'Aucun fichier vidéo MP4 trouvé.');
    }

    $data = $request->validate([
        'cloudflare_video_id' => ['required', 'string', 'max:255'],
    ], [], [
        'cloudflare_video_id' => 'Cloudflare Video ID',
    ]);

    $videoId = trim((string) $data['cloudflare_video_id']);

    // ✅ créer/maj l’asset stream
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

    // ✅ publier maintenant
    $product->status      = 'published';
    $product->reviewed_at = now();
    $product->admin_note  = null;
    $product->save();

    // URL publique
    $publicUrl = $product->slug
        ? route('marketplace.show', $product->slug)
        : route('marketplace.index');

    // ✅ notif vendeur (tu as déjà VendorProductReviewedNotification)
    $vendor = $product->vendor ?: $this->getVendor($product);
    if ($vendor) {
        $vendor->notify(new \App\Notifications\VendorProductReviewedNotification(
            productId: $product->id,
            productTitle: $product->title,
            status: 'approved',
            message: "🎉 Ta vidéo « {$product->title} » a été approuvée et publiée sur la marketplace.",
            url: $publicUrl
        ));
    }

    // ✅ notif autres users (optionnel, tu le fais déjà dans approve())
    \App\Models\User::query()
        ->where('id', '!=', $product->user_id)
        ->chunkById(500, function ($users) use ($product, $publicUrl) {
            foreach ($users as $u) {
                $u->notify(new \App\Notifications\UserNewMarketplaceContentNotification(
                    productId: $product->id,
                    productTitle: $product->title,
                    url: $publicUrl
                ));
            }
        });

    // ✅ notif admin (optionnel)
    if (class_exists(\App\Models\AdminNotification::class)) {
        \App\Models\AdminNotification::create([
            'type'    => 'product_published',
            'title'   => 'Vidéo publiée',
            'message' => $product->title,
            'url'     => route('admin.marketplace.show', $product),
            'read_at' => null,
        ]);
    }

    return redirect()->route('admin.marketplace.show', $product)
        ->with('success', '✅ Vidéo publiée (Cloudflare OK).');
}



}
