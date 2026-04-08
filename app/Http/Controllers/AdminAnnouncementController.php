<?php

namespace App\Http\Controllers;

use App\Models\Announcement;
use App\Models\AnnouncementAttachment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Http\Controllers\Controller;

class AdminAnnouncementController extends Controller
{
    public function index()
    {
        $announcements = Announcement::query()
            ->orderByDesc('published_at')
            ->orderByDesc('id')
            ->paginate(20);

        return view('admin.announcements.index', compact('announcements'));
    }

    public function create()
    {
        return view('admin.announcements.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'title'        => ['required', 'string', 'max:255'],
            'excerpt'      => ['nullable', 'string', 'max:500'],
            'content'      => ['nullable', 'string'],
            'is_published' => ['nullable', 'boolean'],
            'published_at' => ['nullable', 'date'],
            'files'        => ['nullable', 'array', 'max:20'],
            'files.*'      => ['file', 'mimes:jpg,jpeg,png,webp,gif,pdf,doc,docx', 'max:20480'],
        ]);

        $data['is_published'] = (bool) ($data['is_published'] ?? false);
        unset($data['files']);

        $announcement = Announcement::create($data);

        $this->storeFiles($request, $announcement);

        return redirect()->route('admin.announcements.index')->with('success', 'Annonce créée.');
    }

    public function edit(Announcement $announcement)
    {
        $announcement->load('attachments');
        return view('admin.announcements.edit', compact('announcement'));
    }

    public function update(Request $request, Announcement $announcement)
    {
        $data = $request->validate([
            'title'        => ['required', 'string', 'max:255'],
            'excerpt'      => ['nullable', 'string', 'max:500'],
            'content'      => ['nullable', 'string'],
            'is_published' => ['nullable', 'boolean'],
            'published_at' => ['nullable', 'date'],
            'files'        => ['nullable', 'array', 'max:20'],
            'files.*'      => ['file', 'mimes:jpg,jpeg,png,webp,gif,pdf,doc,docx', 'max:20480'],
            'remove_ids'   => ['nullable', 'array'],
            'remove_ids.*' => ['integer'],
        ]);

        $data['is_published'] = (bool) ($data['is_published'] ?? false);
        unset($data['files'], $data['remove_ids']);

        $announcement->update($data);

        // Supprimer les fichiers cochés
        if ($request->filled('remove_ids')) {
            $toRemove = AnnouncementAttachment::where('announcement_id', $announcement->id)
                ->whereIn('id', $request->input('remove_ids'))
                ->get();

            foreach ($toRemove as $att) {
                Storage::disk('public')->delete($att->path);
                $att->delete();
            }
        }

        $this->storeFiles($request, $announcement);

        return redirect()->route('admin.announcements.index')->with('success', 'Annonce mise à jour.');
    }

    public function destroy(Announcement $announcement)
    {
        if (!session('is_admin')) {
            abort(403, 'Action réservée aux administrateurs.');
        }

        // Supprimer les fichiers liés
        foreach ($announcement->attachments as $att) {
            Storage::disk('public')->delete($att->path);
        }

        $announcement->delete();
        return back()->with('success', 'Annonce supprimée.');
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    private function storeFiles(Request $request, Announcement $announcement): void
    {
        if (!$request->hasFile('files')) return;

        $sort = $announcement->attachments()->max('sort') ?? 0;

        foreach ($request->file('files') as $file) {
            $path         = $file->store('announcements', 'public');
            $originalName = $file->getClientOriginalName();
            $ext          = strtolower($file->getClientOriginalExtension());

            $type = match(true) {
                in_array($ext, ['jpg','jpeg','png','webp','gif']) => 'image',
                $ext === 'pdf'                                    => 'pdf',
                default                                           => 'doc',
            };

            $announcement->attachments()->create([
                'path'          => $path,
                'type'          => $type,
                'original_name' => $originalName,
                'sort'          => ++$sort,
            ]);
        }
    }
}
