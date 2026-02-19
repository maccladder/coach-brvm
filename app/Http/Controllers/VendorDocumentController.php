<?php

namespace App\Http\Controllers;

use App\Models\Document;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

class VendorDocumentController extends Controller
{
    public function index(Request $request)
    {
        $vendorId = $request->user()->id;

        $documents = Document::query()
            ->where('vendor_id', $vendorId)
            ->latest()
            ->paginate(15);

        return view('vendor.documents.index', compact('documents'));
    }

    public function create()
    {
        return view('vendor.documents.create');
    }

    public function store(Request $request)
    {
        $vendorId = $request->user()->id;

        $data = $request->validate([
            'title'       => ['required','string','max:150'],
            'type'        => ['required','string','max:50'], // ex: etude_marche / business_plan
            'country'     => ['nullable','string','max:80'],
            'price'       => ['required','integer','min:0'],
            'description' => ['nullable','string','max:5000'],
            'file'        => ['required','file','max:51200','mimes:pdf,doc,docx,ppt,pptx,xls,xlsx,zip'], // ajuste
        ]);

        $slugBase = Str::slug($data['title']);
        $slug = $slugBase . '-' . Str::lower(Str::random(6));

        $path = $request->file('file')->store('documents/vendor', 'public');

        $doc = Document::create([
            'vendor_id'    => $vendorId,
            'title'        => $data['title'],
            'slug'         => $slug,
            'type'         => $data['type'],
            'country'      => $data['country'] ?? null,
            'price'        => (int) $data['price'],
            'description'  => $data['description'] ?? null,
            'file_path'    => $path,

            // ✅ en attente de validation admin
            'status'       => 'pending',
            'is_active'    => false,
        ]);

        return redirect()
            ->route('vendor.documents.edit', $doc)
            ->with('success', '✅ Document envoyé. Statut: en attente de validation admin.');
    }

    public function edit(Request $request, Document $document)
    {
        $this->authorizeVendor($request, $document);

        return view('vendor.documents.edit', compact('document'));
    }

    public function update(Request $request, Document $document)
    {
        $this->authorizeVendor($request, $document);

        $data = $request->validate([
            'title'       => ['required','string','max:150'],
            'type'        => ['required','string','max:50'],
            'country'     => ['nullable','string','max:80'],
            'price'       => ['required','integer','min:0'],
            'description' => ['nullable','string','max:5000'],
            'file'        => ['nullable','file','max:51200','mimes:pdf,doc,docx,ppt,pptx,xls,xlsx,zip'],
        ]);

        // Si rejeté, la mise à jour remet en pending
        $willResetToPending = in_array($document->status, ['rejected'], true);

        if ($request->hasFile('file')) {
            // delete old
            if ($document->file_path && Storage::disk('public')->exists($document->file_path)) {
                Storage::disk('public')->delete($document->file_path);
            }
            $document->file_path = $request->file('file')->store('documents/vendor', 'public');
        }

        $document->title = $data['title'];
        $document->type = $data['type'];
        $document->country = $data['country'] ?? null;
        $document->price = (int) $data['price'];
        $document->description = $data['description'] ?? null;

        if ($willResetToPending) {
            $document->status = 'pending';
            $document->is_active = false;
            $document->rejected_note = null;
            $document->approved_at = null;
            $document->approved_by = null;
        }

        $document->save();

        return back()->with('success', '✅ Modifications enregistrées.');
    }

    // Optionnel : submit explicite (si tu veux un bouton "soumettre")
    public function submit(Request $request, Document $document)
    {
        $this->authorizeVendor($request, $document);

        $document->status = 'pending';
        $document->is_active = false;
        $document->save();

        return back()->with('success', '✅ Document soumis pour validation admin.');
    }

    public function destroy(Request $request, Document $document)
    {
        $this->authorizeVendor($request, $document);

        if ($document->file_path && Storage::disk('public')->exists($document->file_path)) {
            Storage::disk('public')->delete($document->file_path);
        }

        $document->delete();

        return redirect()->route('vendor.documents.index')->with('success', '🗑️ Document supprimé.');
    }

    private function authorizeVendor(Request $request, Document $document): void
    {
        abort_unless((int)$document->vendor_id === (int)$request->user()->id, 403);
    }
}
