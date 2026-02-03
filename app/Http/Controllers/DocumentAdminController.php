<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Document;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

class DocumentAdminController extends Controller
{
    public function index()
    {
        $documents = Document::latest()->paginate(20);
        return view('admin.documents.index', compact('documents'));
    }

    public function create()
    {
        $types = $this->types();
        return view('admin.documents.create', compact('types'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'title'       => ['required','string','max:255'],
            'type'        => ['required','in:market_study,business_plan,funding_dossier'],
            'sector_id'   => ['nullable','integer'],
            'country'     => ['nullable','string','max:120'],
            'price'       => ['required','integer','min:0'],
            'description' => ['nullable','string'],
            'pdf'         => ['required','file','mimes:pdf','max:51200'], // 50MB
            'is_active'   => ['nullable','boolean'],
        ]);

        $filePath = $request->file('pdf')->store('documents'); // storage/app/documents/...

        $doc = Document::create([
            'title'       => $data['title'],
            'slug'        => $this->uniqueSlug($data['title']),
            'type'        => $data['type'],
            'sector_id'   => $data['sector_id'] ?? null,
            'country'     => $data['country'] ?? null,
            'price'       => $data['price'],
            'description' => $data['description'] ?? null,
            'file_path'   => $filePath,
            'is_active'   => (bool)($data['is_active'] ?? true),
        ]);

        return redirect()->route('admin.documents.index')
            ->with('success', "Document ajouté : {$doc->title}");
    }

    public function edit(Document $document)
    {
        $types = $this->types();
        return view('admin.documents.edit', compact('document','types'));
    }

    public function update(Request $request, Document $document)
    {
        $data = $request->validate([
            'title'       => ['required','string','max:255'],
            'type'        => ['required','in:market_study,business_plan,funding_dossier'],
            'sector_id'   => ['nullable','integer'],
            'country'     => ['nullable','string','max:120'],
            'price'       => ['required','integer','min:0'],
            'description' => ['nullable','string'],
            'pdf'         => ['nullable','file','mimes:pdf','max:51200'],
            'is_active'   => ['nullable','boolean'],
        ]);

        // PDF optionnel
        if ($request->hasFile('pdf')) {
            // supprimer ancien fichier
            if ($document->file_path && Storage::exists($document->file_path)) {
                Storage::delete($document->file_path);
            }
            $document->file_path = $request->file('pdf')->store('documents');
        }

        // slug : on le régénère seulement si le titre change
        if ($document->title !== $data['title']) {
            $document->slug = $this->uniqueSlug($data['title'], $document->id);
        }

        $document->fill([
            'title'       => $data['title'],
            'type'        => $data['type'],
            'sector_id'   => $data['sector_id'] ?? null,
            'country'     => $data['country'] ?? null,
            'price'       => $data['price'],
            'description' => $data['description'] ?? null,
            'is_active'   => (bool)($data['is_active'] ?? false),
        ])->save();

        return redirect()->route('admin.documents.index')
            ->with('success', "Document mis à jour : {$document->title}");
    }

    public function destroy(Document $document)
    {
        // (Option) Empêcher suppression si déjà vendu
        if ($document->purchases()->exists()) {
            return back()->with('error', "Impossible : ce document a déjà des ventes.");
        }

        if ($document->file_path && Storage::exists($document->file_path)) {
            Storage::delete($document->file_path);
        }

        $document->delete();

        return back()->with('success', "Document supprimé.");
    }

    public function toggle(Document $document)
    {
        $document->is_active = !$document->is_active;
        $document->save();

        return back()->with('success', $document->is_active ? "Activé." : "Désactivé.");
    }

    private function types(): array
    {
        return [
            'market_study'    => 'Étude de marché',
            'business_plan'   => 'Business plan',
            'funding_dossier' => 'Dossier financement / banque',
        ];
    }

    private function uniqueSlug(string $title, ?int $ignoreId = null): string
    {
        $slug = Str::slug($title);
        $base = $slug;
        $i = 2;

        while (
            Document::where('slug', $slug)
                ->when($ignoreId, fn($q) => $q->where('id', '!=', $ignoreId))
                ->exists()
        ) {
            $slug = $base . '-' . $i;
            $i++;
        }

        return $slug;
    }
}
