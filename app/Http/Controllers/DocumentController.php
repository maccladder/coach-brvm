<?php

namespace App\Http\Controllers;

use App\Models\Document;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class DocumentController extends Controller
{
    private array $types = [
        'market_study'    => 'Étude de marché',
        'business_plan'   => 'Business plan',
        'funding_dossier' => 'Dossier financement / banque',
    ];

    public function index(Request $request)
    {
        $q = Document::query()->active();

        if ($request->filled('type')) {
            $q->where('type', $request->type);
        }

        if ($request->filled('sector_id')) {
            $q->where('sector_id', $request->sector_id);
        }

        if ($request->filled('min_price')) {
            $q->where('price', '>=', (int) $request->min_price);
        }

        if ($request->filled('max_price')) {
            $q->where('price', '<=', (int) $request->max_price);
        }

        if ($request->filled('search')) {
            $s = $request->search;
            $q->where(function ($qq) use ($s) {
                $qq->where('title', 'like', "%$s%")
                   ->orWhere('description', 'like', "%$s%");
            });
        }

        $documents = $q->latest()->paginate(12)->withQueryString();

        $types = $this->types;

        return view('docs_public.index', compact('documents', 'types'));
    }

    public function show($slug)
    {
        $document = Document::query()
            ->active()
            ->where('slug', $slug)
            ->firstOrFail();

        $types = $this->types;

        return view('docs_public.show', compact('document', 'types'));
    }

    /**
     * ✅ Mes documents = uniquement ceux achetés (paid)
     */
    public function myDocuments(Request $request)
    {
        $types = $this->types;

        $documents = Document::query()
            ->active()
            ->whereHas('purchases', function ($q) {
                $q->paid()->where('user_id', auth()->id());
            })
            ->latest()
            ->paginate(12);

        return view('docs_client.mine', compact('documents', 'types'));
    }

    /**
     * ✅ Télécharger : uniquement si acheté (paid)
     */
    public function download($id)
    {
        $document = Document::query()
            ->active()
            ->findOrFail($id);

        if (!$document->isBoughtBy(auth()->user())) {
            abort(403, 'Accès refusé. Document non acheté.');
        }

        $path = $document->file_path;

        if (!$path) {
            abort(404, 'Fichier non disponible.');
        }

        if (!Storage::disk('public')->exists($path)) {
            abort(404, 'Fichier introuvable sur le serveur.');
        }

        $downloadName = ($document->slug ?? 'document') . '.pdf';

        return Storage::disk('public')->download($path, $downloadName);
    }
}
