<?php

namespace App\Http\Controllers;

use App\Models\MarketplaceProduct;
use App\Models\ProductFile;
use App\Services\Marketplace\ProductFileService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

/**
 * Gestionnaire « Fichiers téléchargeables » d'un produit, partagé par l'admin
 * (routes admin.marketplace.files.*, protégées par admin.code) et le vendeur
 * (routes vendor.products.files.*, policy manageFiles : ses propres produits uniquement).
 */
class MarketplaceProductFileController extends Controller
{
    public function __construct(private ProductFileService $files)
    {
    }

    private function isAdminRoute(Request $request): bool
    {
        return $request->routeIs('admin.*');
    }

    private function authorizeManage(Request $request, MarketplaceProduct $product, ?ProductFile $file = null): void
    {
        if ($file) {
            abort_unless((int) $file->product_id === (int) $product->id, 404);
        }

        if (!$this->isAdminRoute($request)) {
            Gate::forUser($request->user())->authorize('manageFiles', $product);
        }
    }

    private function backToEdit(Request $request, MarketplaceProduct $product)
    {
        $route = $this->isAdminRoute($request) ? 'admin.marketplace.edit' : 'vendor.products.edit';

        return redirect()->route($route, $product)->withFragment('fichiers');
    }

    /** Vendeur : pas de modification pendant la validation admin (même règle que update()). */
    private function lockedForVendor(Request $request, MarketplaceProduct $product)
    {
        if (!$this->isAdminRoute($request) && $product->status === 'pending') {
            return $this->backToEdit($request, $product)
                ->with('pf_warning', '⏳ Produit en validation admin : modification des fichiers désactivée.');
        }

        return null;
    }

    public function store(Request $request, MarketplaceProduct $product)
    {
        $this->authorizeManage($request, $product);
        if ($locked = $this->lockedForVendor($request, $product)) return $locked;

        if (!in_array($product->type, ProductFileService::DOWNLOADABLE_TYPES, true)) {
            return $this->backToEdit($request, $product)
                ->with('pf_warning', 'Les fichiers téléchargeables concernent uniquement les livres et les logiciels.');
        }

        $extensions = config('marketplace.files_extensions');
        $maxMb      = (int) config('marketplace.files_max_mb', 500);
        $phpLimit   = ini_get('upload_max_filesize');

        $request->validateWithBag('productFiles', [
            'files'       => ['required', 'array', 'min:1', 'max:20'],
            'files.*'     => ['required', 'file', 'extensions:' . implode(',', $extensions), 'max:' . ($maxMb * 1024)],
            'labels'      => ['nullable', 'array'],
            'labels.*'    => ['nullable', 'string', 'max:120'],
            'platforms'   => ['nullable', 'array'],
            'platforms.*' => ['nullable', Rule::in(array_keys(ProductFile::PLATFORMS))],
            'notify'      => ['nullable', 'boolean'],
        ], [
            'files.required'     => 'Choisis au moins un fichier à ajouter.',
            'files.max'          => '20 fichiers maximum par envoi.',
            'files.*.uploaded'   => "Un fichier dépasse la limite d'upload du serveur ({$phpLimit}). Envoie-le seul, ou demande à relever la limite PHP.",
            'files.*.extensions' => 'Format non autorisé. Formats acceptés : ' . implode(', ', $extensions) . '.',
            'files.*.max'        => "Chaque fichier doit faire au plus {$maxMb} Mo.",
            'labels.*.max'       => 'Le libellé ne doit pas dépasser 120 caractères.',
            'platforms.*.in'     => 'Plateforme inconnue.',
        ]);

        $added = [];
        foreach ($request->file('files') as $i => $upload) {
            // Sans JS, aucune plateforme n'est envoyée : on la devine depuis l'extension
            $platform = $request->has("platforms.$i")
                ? ($request->input("platforms.$i") ?: null)
                : $this->files->guessPlatform($upload->getClientOriginalName());

            // Vendeur + produit publié + exécutable : invisible des acheteurs jusqu'à validation admin
            $status = !$this->isAdminRoute($request) && $this->files->requiresReview($product, $upload->getClientOriginalName())
                ? 'pending'
                : 'approved';

            $added[] = $this->files->addFile($product, $upload, $request->input("labels.$i"), $platform, $status);
        }

        $pending = array_values(array_filter($added, fn ($f) => $f->isPending()));
        foreach ($pending as $file) {
            $this->files->notifyAdminsOfPending($file);
        }

        $message = count($added) === 1
            ? '✅ Fichier ajouté : ' . $added[0]->label . '.'
            : '✅ ' . count($added) . ' fichiers ajoutés.';
        if ($pending) {
            $message .= ' ⏳ ' . count($pending) . ' fichier(s) en attente de validation admin : invisibles pour les acheteurs d’ici là.';
        }

        // Notification réservée à l'admin (pas aux stagiaires ni aux vendeurs)
        if ($this->isAdminRoute($request) && session('is_admin') && $request->boolean('notify')) {
            $count = $this->files->notifyBuyers($product, array_map(fn ($f) => $f->label, $added));
            $message .= $count > 0
                ? " 📧 {$count} acheteur(s) prévenu(s) par email."
                : ' Aucun acheteur à prévenir pour l’instant.';
        }

        return $this->backToEdit($request, $product)->with('pf_success', $message);
    }

    public function update(Request $request, MarketplaceProduct $product, ProductFile $file)
    {
        $this->authorizeManage($request, $product, $file);
        if ($locked = $this->lockedForVendor($request, $product)) return $locked;

        $data = $request->validateWithBag('productFiles', [
            'label'    => ['required', 'string', 'max:120'],
            'platform' => ['nullable', Rule::in(array_keys(ProductFile::PLATFORMS))],
        ], [
            'label.required' => 'Le libellé est obligatoire.',
            'label.max'      => 'Le libellé ne doit pas dépasser 120 caractères.',
        ]);

        $file->update([
            'label'    => trim($data['label']),
            'platform' => $data['platform'] ?? null,
        ]);

        return $this->backToEdit($request, $product)->with('pf_success', '✅ Fichier mis à jour.');
    }

    public function move(Request $request, MarketplaceProduct $product, ProductFile $file)
    {
        $this->authorizeManage($request, $product, $file);
        if ($locked = $this->lockedForVendor($request, $product)) return $locked;

        $request->validate(['direction' => ['required', 'in:up,down']]);
        $this->files->move($file, $request->input('direction') === 'up' ? -1 : 1);

        return $this->backToEdit($request, $product);
    }

    public function destroy(Request $request, MarketplaceProduct $product, ProductFile $file)
    {
        $this->authorizeManage($request, $product, $file);
        if ($locked = $this->lockedForVendor($request, $product)) return $locked;

        $label = $file->label;
        $this->files->deleteFile($file);

        return $this->backToEdit($request, $product)->with('pf_success', "🗑️ Fichier supprimé : {$label}.");
    }

    /** Admin uniquement (pas les stagiaires) : rend visible un fichier vendeur en attente. */
    public function approve(Request $request, MarketplaceProduct $product, ProductFile $file)
    {
        $this->authorizeManage($request, $product, $file);
        abort_unless($this->isAdminRoute($request) && session('is_admin'), 403, 'Action réservée aux administrateurs.');

        if (!$file->isPending() && !$file->isRejected()) {
            return $this->backToEdit($request, $product)->with('pf_warning', 'Ce fichier est déjà approuvé.');
        }

        $isReplacement = (bool) $file->replaces_file_id;
        $visible = $this->files->approve($file);

        $message = $isReplacement
            ? "✅ Remplacement approuvé : « {$visible->label} » est désormais servi aux acheteurs."
            : "✅ Fichier approuvé : « {$visible->label} » est visible des acheteurs.";

        if ($request->boolean('notify')) {
            $count = $this->files->notifyBuyers($product, [$visible->label]);
            $message .= $count > 0 ? " 📧 {$count} acheteur(s) prévenu(s) par email." : '';
        }

        return $this->backToEdit($request, $product)->with('pf_success', $message);
    }

    public function reject(Request $request, MarketplaceProduct $product, ProductFile $file)
    {
        $this->authorizeManage($request, $product, $file);
        abort_unless($this->isAdminRoute($request) && session('is_admin'), 403, 'Action réservée aux administrateurs.');

        if (!$file->isPending()) {
            return $this->backToEdit($request, $product)->with('pf_warning', 'Seul un fichier en attente peut être refusé.');
        }

        $data = $request->validateWithBag('productFiles', [
            'review_note' => ['nullable', 'string', 'max:500'],
        ], ['review_note.max' => 'Le motif ne doit pas dépasser 500 caractères.']);

        $this->files->reject($file, $data['review_note'] ?? null);

        return $this->backToEdit($request, $product)->with('pf_success', "🚫 Fichier refusé : « {$file->label} ». Il reste invisible pour les acheteurs.");
    }

    /** Aperçu du fichier par l'admin ou son vendeur (pas de compteur de téléchargements). */
    public function download(Request $request, MarketplaceProduct $product, ProductFile $file)
    {
        $this->authorizeManage($request, $product, $file);

        abort_unless(Storage::disk($file->disk)->exists($file->path), 404, 'Fichier introuvable (storage).');

        return Storage::disk($file->disk)->download($file->path, $file->original_name);
    }
}
