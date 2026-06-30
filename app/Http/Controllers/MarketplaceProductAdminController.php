<?php

namespace App\Http\Controllers;

use App\Models\AdminNotification;
use App\Models\MarketplaceAsset;
use App\Models\MarketplaceCategory;
use App\Models\MarketplaceProduct;
use App\Models\User;
use App\Notifications\UserNewMarketplaceContentNotification;
use App\Notifications\VendorProductReviewedNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Imagick;

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

    private function sanitizeGameHtml(string $html): string
    {
        $html = preg_replace(
            '/<script\b[^>]*\bsrc\s*=\s*["\']https?:\/\/[^"\']*["\'][^>]*>.*?<\/script>/si',
            '', $html
        );
        $html = preg_replace(
            '/<link\b[^>]*\bhref\s*=\s*["\']https?:\/\/[^"\']*["\'][^>]*\/?>/si',
            '', $html
        );
        $html = preg_replace(
            '/<iframe\b[^>]*\bsrc\s*=\s*["\']https?:\/\/[^"\']*["\'][^>]*>.*?<\/iframe>/si',
            '', $html
        );
        return $html;
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
            'type'        => ['required', 'in:video,book,software,game'],
            'description' => ['nullable', 'string'],
            'price'       => ['required', 'integer', 'min:0'],
            'status'      => ['required', 'in:draft,published,pending,rejected'],
            'is_featured' => ['nullable', 'boolean'],
            'support_whatsapp' => ['nullable', 'string', 'max:32'],
            'udemy_url'   => ['nullable', 'url', 'max:500'],
            'cover'       => ['nullable', 'image', 'max:' . self::MAX_COVER_KB],
            'file'        => ['nullable', 'file', 'max:' . self::MAX_FILE_KB],
            'cloudflare_video_id' => ['nullable', 'string', 'max:255'],
            'game_html_file' => ['nullable', 'file', 'mimes:html', 'max:10240'],
        ], [], [
            'file'  => 'fichier produit',
            'cover' => 'image de couverture',
            'udemy_url' => 'lien Udemy',
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

        if ($data['type'] === 'game') {
            $request->validate([
                'game_html_file' => ['required', 'file', 'mimes:html', 'max:10240'],
            ], [], ['game_html_file' => 'fichier HTML du jeu']);
        }

        // Lire et sanitiser le HTML du jeu si fourni
        $gameHtml = null;
        if ($data['type'] === 'game' && $request->hasFile('game_html_file')) {
            $rawHtml = file_get_contents($request->file('game_html_file')->getRealPath());
            $gameHtml = $this->sanitizeGameHtml($rawHtml);
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
            'udemy_url'        => $data['udemy_url'] ?? null,
            'price'            => (int) $data['price'],
            'status'           => $data['status'],
            'is_featured'      => (bool) ($data['is_featured'] ?? false),
            'cover_image_path' => $coverPath,
            'pages_count'      => null,
            'game_html'        => $gameHtml,
        ]);

        if ($product->type === 'game') {
            return redirect()->route('admin.marketplace.index')->with('success', 'Jeu créé ✅');
        }

        if ($product->type === 'video') {
            $product->assets()->create([
                'kind'            => 'stream',
                'path'            => null,
                'url'             => trim((string) $request->input('cloudflare_video_id')),
                'label'           => 'Vidéo',
                'is_downloadable' => false,
            ]);

            return redirect()->route('admin.marketplace.index')->with('success', 'Produit vidéo créé ✅');
        }

        // book/software => fichier obligatoire
        if ($request->hasFile('file')) {
            $uploadedPath = $request->file('file')->store('marketplace/files', 'public');

            $label = match ($product->type) {
                'book'     => 'PDF',
                'software' => 'Logiciel',
                default    => 'Fichier',
            };

            $fileAsset = $product->assets()->create([
                'kind'            => 'file',
                'path'            => $uploadedPath,
                'url'             => null,
                'label'           => $label,
                'is_downloadable' => true,
            ]);

            // ✅ pages_count + previews p1..p5 (UNIQUEMENT book)
            if ($product->type === 'book') {
                $this->generatePdfMetaAndPreviews($product, $fileAsset);
            }
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
            'type'        => ['required', 'in:video,book,software,game'],
            'description' => ['nullable', 'string'],
            'price'       => ['required', 'integer', 'min:0'],
            'status'      => ['required', 'in:draft,published,pending,rejected'],
            'is_featured' => ['nullable', 'boolean'],
            'support_whatsapp' => ['nullable', 'string', 'max:32'],
            'udemy_url'   => ['nullable', 'url', 'max:500'],
            'cover'       => ['nullable', 'image', 'max:' . self::MAX_COVER_KB],
            'file'        => ['nullable', 'file', 'max:' . self::MAX_FILE_KB],
            'cloudflare_video_id' => ['nullable', 'string', 'max:255'],
            'game_html_file' => ['nullable', 'file', 'mimes:html', 'max:10240'],
        ], [], [
            'udemy_url' => 'lien Udemy',
        ]);

        $data['support_whatsapp'] = $this->cleanWhatsapp($data['support_whatsapp'] ?? null);

        $hasFileAsset = $product->assets->firstWhere('kind', 'file') !== null;
        $requiresFile = in_array($data['type'], ['book', 'software'], true);

        if ($requiresFile && !$hasFileAsset && !$request->hasFile('file')) {
            $request->validate(['file' => ['required']], [
                'file.required' => 'Le fichier produit est obligatoire pour ce type.',
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
            ], [], ['cloudflare_video_id' => 'Cloudflare Video ID']);
        }

        $coverPath = $product->cover_image_path;
        if ($request->hasFile('cover')) {
            $coverPath = $request->file('cover')->store('marketplace/covers', 'public');
        }

        $updateData = [
            'category_id'      => $data['category_id'] ?? null,
            'title'            => $data['title'],
            'type'             => $data['type'],
            'description'      => $data['description'] ?? null,
            'support_whatsapp' => $data['support_whatsapp'],
            'udemy_url'        => $data['udemy_url'] ?? null,
            'price'            => (int) $data['price'],
            'status'           => $data['status'],
            'is_featured'      => (bool) ($data['is_featured'] ?? false),
            'cover_image_path' => $coverPath,
        ];

        if ($data['type'] === 'game' && $request->hasFile('game_html_file')) {
            $rawHtml = file_get_contents($request->file('game_html_file')->getRealPath());
            $updateData['game_html'] = $this->sanitizeGameHtml($rawHtml);
        }

        $product->update($updateData);

        // Jeu HTML : pas d'asset fichier
        if ($product->type === 'game') {
            $product->assets()->delete();
            return back()->with('success', 'Jeu mis à jour ✅');
        }

        // Switch vidéo
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
            $product->update(['pages_count' => null]);

            return back()->with('success', 'Produit mis à jour ✅');
        }

        // ✅ si on remplace le fichier PDF/ZIP
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
                $fileAsset = $asset;
            } else {
                $fileAsset = $product->assets()->create([
                    'kind' => 'file', 'path' => $uploadedPath, 'url' => null, 'label' => $label, 'is_downloadable' => true,
                ]);
            }

            if ($product->type === 'book') {
                $this->generatePdfMetaAndPreviews($product, $fileAsset, true);
            } else {
                $product->update(['pages_count' => null]);
            }
        }

        $product->assets()->where('kind', 'stream')->delete();

        return back()->with('success', 'Produit mis à jour ✅');
    }

    private function generatePdfMetaAndPreviews(MarketplaceProduct $product, MarketplaceAsset $fileAsset, bool $wipeOld = false): void
    {
        if (!$fileAsset->path) return;

        $disk    = 'public';
        $pdfPath = Storage::disk($disk)->path($fileAsset->path);

        if (!file_exists($pdfPath)) return;

        if ($wipeOld) {
            Storage::disk($disk)->deleteDirectory("marketplace/previews/{$product->id}");
        }

        // 1) pages_count
        try {
            $im = new \Imagick();
            $im->pingImage($pdfPath);
            $pages = (int) $im->getNumberImages();
            $im->clear();
            $im->destroy();

            if ($pages <= 0) $pages = null;
            $product->update(['pages_count' => $pages]);
        } catch (\Throwable $e) {
            Log::warning('PDF meta (pages_count) failed: ' . $e->getMessage(), ['product_id' => $product->id]);
            $pages = null;
        }

        // 2) previews p1..p5 — chaque page est indépendante
        $max = min(5, (int) ($pages ?: 5));
        Storage::disk($disk)->makeDirectory("marketplace/previews/{$product->id}");

        for ($i = 0; $i < $max; $i++) {
            try {
                $img = new \Imagick();
                $img->setResolution(150, 150);
                $img->readImage($pdfPath . '[' . $i . ']');
                $img->setImageFormat('jpeg');
                $img->setImageCompressionQuality(85);
                $img->stripImage();

                if ($img->getImageWidth() > 1200) {
                    $img->resizeImage(1200, 0, \Imagick::FILTER_LANCZOS, 1);
                }

                $previewPath = "marketplace/previews/{$product->id}/p" . ($i + 1) . ".jpg";
                Storage::disk($disk)->put($previewPath, $img->getImageBlob());

                $img->clear();
                $img->destroy();
            } catch (\Throwable $e) {
                Log::warning("PDF preview page {$i} failed: " . $e->getMessage(), ['product_id' => $product->id]);
            }
        }
    }

    public function destroy(MarketplaceProduct $product)
    {
        if (!session('is_admin')) {
            abort(403, 'Action réservée aux administrateurs.');
        }

        $product->delete();
        return back()->with('success', 'Produit supprimé ✅');
    }

    public function playAdmin(MarketplaceProduct $product)
    {
        if ($product->type !== 'game' || empty($product->game_html)) {
            abort(404, "Ce produit n'est pas un jeu ou n'a pas de HTML.");
        }

        return view('admin.marketplace.products.play', compact('product'));
    }

    public function gameHtmlAdmin(MarketplaceProduct $product)
    {
        if ($product->type !== 'game' || empty($product->game_html)) {
            abort(404);
        }

        $mockUrl = route('paystack.mock-inline');
        $html = str_replace(
            ['https://js.paystack.co/v1/inline.js'],
            $mockUrl,
            $product->game_html
        );

        // Force adminMode = true — l'admin n'a pas besoin d'acheter le jeu
        $html = str_replace('__ADMIN_MODE__', 'true', $html);

        return response($html, 200)
            ->header('Content-Type', 'text/html; charset=UTF-8')
            ->header('X-Frame-Options', 'SAMEORIGIN')
            ->header('Cache-Control', 'no-store');
    }

   public function approve(Request $request, MarketplaceProduct $product)
{
    if ($product->status !== 'pending') {
        return back()->with('warning', "Ce produit n'est pas en attente.");
    }

    $product->load(['assets', 'vendor']);

    // ✅ Vidéo => workflow spécial : page "pré-approuver" (Cloudflare)
    if ($product->type === 'video') {
        return redirect()->route('admin.marketplace.publish.form', $product);
    }

    // Jeu HTML : le contenu est dans game_html, pas dans un asset
    if ($product->type === 'game') {
        if (empty($product->game_html)) {
            return back()->with('warning', "Impossible d'approuver: aucun fichier HTML de jeu attaché.");
        }
    } else {
        // PDF/ZIP/Vidéo : on exige un fichier asset
        if (!$product->assets->firstWhere('kind', 'file')) {
            return back()->with('warning', "Impossible d'approuver: aucun fichier attaché (PDF/ZIP).");
        }
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
            message: "🎉 Félicitations ! Ton produit « {$product->title} » a été approuvé par l'admin et est maintenant visible sur la marketplace.",
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
    if (!session('is_admin')) {
        abort(403, 'Action réservée aux administrateurs.');
    }

    if ($product->status !== 'pending') {
        return back()->with('warning', "Ce produit n'est pas en attente.");
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
            message: "⛔ Ton produit « {$product->title} » a été rejeté par l'admin.\nMotif : {$data['admin_note']}\n👉 Corrige puis soumets à nouveau.",
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
            ->with('warning', "Ce produit n'est pas en attente.");
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
        return back()->with('warning', "Ce produit n'est pas en attente.");
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

    // ✅ créer/maj l'asset stream
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
