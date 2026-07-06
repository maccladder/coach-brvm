<?php

namespace App\Http\Controllers;

use App\Mail\NewProductPendingMail;
use App\Models\AdminNotification;
use App\Models\MarketplaceCategory;
use App\Models\MarketplaceProduct;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class VendorProductController extends Controller
{
    // tailles (KB)
    private const MAX_COVER_KB = 4096;     // 4MB
    private const MAX_PDF_KB   = 51200;    // 50MB
    private const MAX_ZIP_KB   = 409600;   // 400MB
    private const MAX_VIDEO_KB = 512000;   // 500MB (ajuste si tu veux)

    private function isPlatformOwner(Request $request): bool
    {
        return strcasecmp($request->user()->email, config('app.owner_email')) === 0;
    }

    /**
     * Vrai si l'utilisateur peut gérer ce produit vendeur : soit il en est le
     * propriétaire, soit c'est un produit officiel (user_id null) et lui-même
     * est le propriétaire de la plateforme.
     */
    private function ownsProduct(Request $request, MarketplaceProduct $product): bool
    {
        if ($product->user_id === $request->user()->id) {
            return true;
        }

        return $product->user_id === null && $this->isPlatformOwner($request);
    }

    private function cleanWhatsapp(?string $value): ?string
    {
        $raw = trim((string) $value);
        if ($raw === '') return null;

        $digits = preg_replace('/[^0-9]/', '', $raw);
        return $digits ?: null;
    }

    private function typeLabel(string $type): string
    {
        return match ($type) {
            'book'     => 'PDF',
            'software' => 'Logiciel',
            'video'    => 'Vidéo',
            'game'     => 'Jeu HTML',
            default    => strtoupper($type),
        };
    }

    /**
     * Sanitise le HTML d'un jeu : supprime les <script src="http..."> externes
     * et les <iframe src="http..."> imbriquées. Le sandbox de l'iframe parent
     * assure la protection principale.
     */
    private function sanitizeGameHtml(string $html): string
    {
        // Supprimer <script src="https?://..."> (scripts externes)
        $html = preg_replace(
            '/<script\b[^>]*\bsrc\s*=\s*["\']https?:\/\/[^"\']*["\'][^>]*>.*?<\/script>/si',
            '',
            $html
        );
        // Supprimer <link rel="stylesheet" href="https?://..."> externes
        $html = preg_replace(
            '/<link\b[^>]*\bhref\s*=\s*["\']https?:\/\/[^"\']*["\'][^>]*\/?>/si',
            '',
            $html
        );
        // Supprimer les iframes imbriquées pointant vers des URLs externes
        $html = preg_replace(
            '/<iframe\b[^>]*\bsrc\s*=\s*["\']https?:\/\/[^"\']*["\'][^>]*>.*?<\/iframe>/si',
            '',
            $html
        );
        return $html;
    }

    public function index(Request $request)
    {
        $q = MarketplaceProduct::query()
            ->with('category')
            ->where('user_id', $request->user()->id)
            ->latest();

        if ($request->filled('status')) {
            $q->where('status', (string) $request->input('status'));
        }

        $products = $q->paginate(15)->withQueryString();

        return view('vendor.products.index', compact('products'));
    }

    public function create()
    {
        $categories = MarketplaceCategory::orderBy('name')->get();

        $types = [
            'book'     => 'Livre (PDF)',
            'video'    => 'Vidéo',
            'software' => 'Logiciel (ZIP/RAR)',
            'game'     => 'Jeu (HTML)',
        ];

        return view('vendor.products.create', compact('categories', 'types'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'category_id'      => ['required','integer','exists:marketplace_categories,id'],
            'title'            => ['required','string','max:160'],
            'type'             => ['required','in:book,video,software,game'],
            'description'      => ['nullable','string'],
            'support_whatsapp' => ['nullable','string','max:30'],
            'udemy_url'        => ['nullable','url','max:500'],
            'price'            => ['required','numeric','min:0'],
            'cover_image'      => ['nullable','image','max:' . self::MAX_COVER_KB],

            // fichier produit (optionnel au draft, sauf game qui passe par game_html_file)
            'file'             => ['nullable','file'],
            'game_html_file'   => ['nullable','file','mimes:html','max:10240'], // 10 MB max
        ], [], [
            'udemy_url' => 'lien Udemy',
        ]);

        $data['support_whatsapp'] = $this->cleanWhatsapp($data['support_whatsapp'] ?? null);

        // Validation conditionnelle du fichier selon type (si fourni)
        if ($request->hasFile('file')) {
            if ($data['type'] === 'book') {
                $request->validate([
                    'file' => ['file','mimes:pdf','max:' . self::MAX_PDF_KB],
                ], [], ['file' => 'fichier PDF']);
            } elseif ($data['type'] === 'software') {
                $request->validate([
                    'file' => ['file','mimes:zip,rar','max:' . self::MAX_ZIP_KB],
                ], [], ['file' => 'archive ZIP/RAR']);
            } elseif ($data['type'] === 'video') {
                $request->validate([
                    'file' => ['file','mimes:mp4,mov,m4v,avi','max:' . self::MAX_VIDEO_KB],
                ], [], ['file' => 'fichier vidéo']);
            }
        }

        // slug unique
        $slug = Str::slug($data['title']);
        $base = $slug;
        $i = 2;
        while (MarketplaceProduct::where('slug', $slug)->exists()) {
            $slug = $base.'-'.$i++;
        }

        // cover
        $coverPath = null;
        if ($request->hasFile('cover_image')) {
            $coverPath = $request->file('cover_image')->store('marketplace/covers', 'public');
        }

        // Lire et sanitiser le HTML du jeu si fourni
        $gameHtml = null;
        if ($data['type'] === 'game' && $request->hasFile('game_html_file')) {
            $rawHtml = file_get_contents($request->file('game_html_file')->getRealPath());
            $gameHtml = $this->sanitizeGameHtml($rawHtml);
        }

        // Le propriétaire de la plateforme n'est pas un vendeur tiers : ses produits
        // sont traités comme officiels (user_id null) pour ne pas être bloqués par
        // le garde-fou ownership de l'affiliation.
        $isOwner = $this->isPlatformOwner($request);

        // create product (draft)
        $product = MarketplaceProduct::create([
            'user_id'          => $isOwner ? null : $request->user()->id,
            'category_id'      => $data['category_id'],
            'title'            => $data['title'],
            'slug'             => $slug,
            'type'             => $data['type'],
            'description'      => $data['description'] ?? null,
            'support_whatsapp' => $data['support_whatsapp'],
            'udemy_url'        => $data['udemy_url'] ?? null,
            'price'            => $data['price'],
            'cover_image_path' => $coverPath,
            'status'           => 'draft',
            'is_featured'      => false,
            'pages_count'      => null,
            'game_html'        => $gameHtml,
        ]);

        // asset si fichier uploadé (book / software / video)
        if ($request->hasFile('file')) {
            $uploadedPath = $request->file('file')->store('marketplace/files', 'public');

            $product->assets()->create([
                'kind'            => 'file',
                'path'            => $uploadedPath,
                'url'             => null,
                'label'           => $this->typeLabel($product->type),
                'is_downloadable' => $product->type !== 'video',
            ]);
        }

        return redirect()->route('vendor.products.edit', $product)
            ->with('success', '✅ Produit créé (brouillon). Ajoute le fichier + cover puis soumets.');
    }

    public function edit(Request $request, MarketplaceProduct $product)
    {
        abort_unless($this->ownsProduct($request, $product), 403);

        $product->load('assets');
        $categories = MarketplaceCategory::orderBy('name')->get();

        $types = [
            'book'     => 'Livre (PDF)',
            'video'    => 'Vidéo',
            'software' => 'Logiciel (ZIP/RAR)',
            'game'     => 'Jeu (HTML)',
        ];

        return view('vendor.products.edit', compact('product', 'categories', 'types'));
    }

    public function update(Request $request, MarketplaceProduct $product)
    {
        abort_unless($this->ownsProduct($request, $product), 403);

        if ($product->status === 'pending') {
            return back()->with('warning', '⏳ Produit en validation : modification désactivée.');
        }

        $product->load('assets');

        $data = $request->validate([
            'category_id'      => ['required','integer','exists:marketplace_categories,id'],
            'title'            => ['required','string','max:160'],
            'type'             => ['required','in:book,video,software,game'],
            'description'      => ['nullable','string'],
            'support_whatsapp' => ['nullable','string','max:30'],
            'udemy_url'        => ['nullable','url','max:500'],
            'price'            => ['required','numeric','min:0'],
            'cover_image'      => ['nullable','image','max:' . self::MAX_COVER_KB],
            'file'             => ['nullable','file'],
            'game_html_file'   => ['nullable','file','mimes:html','max:10240'],
        ], [], [
            'udemy_url' => 'lien Udemy',
        ]);

        $data['support_whatsapp'] = $this->cleanWhatsapp($data['support_whatsapp'] ?? null);

        // Validation conditionnelle si upload file
        if ($request->hasFile('file')) {
            if ($data['type'] === 'book') {
                $request->validate([
                    'file' => ['file','mimes:pdf','max:' . self::MAX_PDF_KB],
                ], [], ['file' => 'fichier PDF']);
            } elseif ($data['type'] === 'software') {
                $request->validate([
                    'file' => ['file','mimes:zip,rar','max:' . self::MAX_ZIP_KB],
                ], [], ['file' => 'archive ZIP/RAR']);
            } elseif ($data['type'] === 'video') {
                $request->validate([
                    'file' => ['file','mimes:mp4,mov,m4v,avi','max:' . self::MAX_VIDEO_KB],
                ], [], ['file' => 'fichier vidéo']);
            }
        }

        if ($request->hasFile('cover_image')) {
            $product->cover_image_path = $request->file('cover_image')->store('marketplace/covers', 'public');
        }

        $product->category_id      = $data['category_id'];
        $product->title            = $data['title'];
        $product->type             = $data['type'];
        $product->description      = $data['description'] ?? null;
        $product->support_whatsapp = $data['support_whatsapp'];
        $product->udemy_url        = $data['udemy_url'] ?? null;
        $product->price            = $data['price'];

        // Mise à jour du HTML de jeu si un nouveau fichier est fourni
        if ($data['type'] === 'game' && $request->hasFile('game_html_file')) {
            $rawHtml = file_get_contents($request->file('game_html_file')->getRealPath());
            $product->game_html = $this->sanitizeGameHtml($rawHtml);
        }

        // si rejeté → repasse en draft
        if ($product->status === 'rejected') {
            $product->status = 'draft';
            $product->admin_note = null;
        }

        $product->save();

        // remplacer/créer asset fichier (book / software / video)
        if ($request->hasFile('file')) {
            $uploadedPath = $request->file('file')->store('marketplace/files', 'public');

            $asset = $product->assets()->where('kind', 'file')->first();

            if ($asset) {
                $asset->update([
                    'path'            => $uploadedPath,
                    'url'             => null,
                    'label'           => $this->typeLabel($product->type),
                    'is_downloadable' => $product->type !== 'video',
                ]);
            } else {
                $product->assets()->create([
                    'kind'            => 'file',
                    'path'            => $uploadedPath,
                    'url'             => null,
                    'label'           => $this->typeLabel($product->type),
                    'is_downloadable' => $product->type !== 'video',
                ]);
            }
        } elseif ($data['type'] !== 'game') {
            // si type a changé, maj label/is_downloadable de l'asset existant
            $asset = $product->assets()->where('kind', 'file')->first();
            if ($asset) {
                $asset->update([
                    'label'           => $this->typeLabel($product->type),
                    'is_downloadable' => $product->type !== 'video',
                ]);
            }
        }

        return back()->with('success', '✅ Produit mis à jour.');
    }

    public function submit(Request $request, MarketplaceProduct $product)
    {
        abort_unless($this->ownsProduct($request, $product), 403);

        if (!in_array($product->status, ['draft','rejected'], true)) {
            return back()->with('warning', 'Action impossible pour ce statut.');
        }

        $product->load('assets');

        if (!$product->title || !$product->category_id || $product->price === null || !$product->type) {
            return back()->with('warning', 'Complète les infos principales avant de soumettre.');
        }

        if (!$product->cover_image_path) {
            return back()->with('warning', 'Ajoute une image de couverture avant de soumettre.');
        }

        // Exiger un fichier ou du HTML selon le type
        if ($product->type === 'game') {
            if (empty($product->game_html)) {
                return back()->with('warning', 'Upload le fichier HTML du jeu avant de soumettre.');
            }
        } else {
            $fileAsset = $product->assets->firstWhere('kind', 'file');
            if (!$fileAsset) {
                return back()->with('warning', 'Ajoute le fichier (PDF/ZIP/VIDÉO) avant de soumettre.');
            }
        }

        $product->status = 'pending';
        $product->submitted_at = now();
        $product->admin_note = null;
        $product->save();

        // notif admin (base de données)
        if (class_exists(AdminNotification::class)) {
            AdminNotification::create([
                'type'    => 'product_pending',
                'title'   => '🕒 Nouveau produit en attente',
                'message' => $product->title,
                'url'     => route('admin.marketplace.show', $product),
                'read_at' => null,
            ]);
        }

        // notif admin (email)
        Mail::to(['maccladder@gmail.com', 'ghislainkouadiodjaha@gmail.com'])
            ->send(new NewProductPendingMail($product));

        return redirect()->route('vendor.products.index')
            ->with('success', '✅ Produit soumis. En attente de validation admin.');
    }
}
