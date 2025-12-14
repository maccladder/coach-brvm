<?php

namespace App\Http\Controllers;


use App\Models\Announcement;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
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
            'title' => ['required', 'string', 'max:255'],
            'excerpt' => ['nullable', 'string', 'max:500'],
            'content' => ['nullable', 'string'],
            'is_published' => ['nullable', 'boolean'],
            'published_at' => ['nullable', 'date'],
            'attachment' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:10240'],
        ]);

        $data['is_published'] = (bool) ($data['is_published'] ?? false);

        if ($request->hasFile('attachment')) {
            $file = $request->file('attachment');
            $path = $file->store('announcements', 'public'); // storage/app/public/announcements

            $data['attachment_path'] = $path;
            $data['attachment_type'] = Str::endsWith(strtolower($file->getClientOriginalName()), '.pdf') ? 'pdf' : 'image';
        }

        Announcement::create($data);

        return redirect()->route('admin.announcements.index')->with('success', 'Annonce créée.');
    }

    public function edit(Announcement $announcement)
    {
        return view('admin.announcements.edit', compact('announcement'));
    }

    public function update(Request $request, Announcement $announcement)
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'excerpt' => ['nullable', 'string', 'max:500'],
            'content' => ['nullable', 'string'],
            'is_published' => ['nullable', 'boolean'],
            'published_at' => ['nullable', 'date'],
            'attachment' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:10240'],
            'remove_attachment' => ['nullable', 'boolean'],
        ]);

        $data['is_published'] = (bool) ($data['is_published'] ?? false);

        if (!empty($data['remove_attachment'])) {
            $data['attachment_path'] = null;
            $data['attachment_type'] = null;
        }

        if ($request->hasFile('attachment')) {
            $file = $request->file('attachment');
            $path = $file->store('announcements', 'public');

            $data['attachment_path'] = $path;
            $data['attachment_type'] = Str::endsWith(strtolower($file->getClientOriginalName()), '.pdf') ? 'pdf' : 'image';
        }

        $announcement->update($data);

        return redirect()->route('admin.announcements.index')->with('success', 'Annonce mise à jour.');
    }

    public function destroy(Announcement $announcement)
    {
        $announcement->delete();
        return back()->with('success', 'Annonce supprimée.');
    }
}
