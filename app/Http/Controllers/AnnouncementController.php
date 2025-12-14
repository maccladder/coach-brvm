<?php

namespace App\Http\Controllers;

use App\Models\Announcement;

class AnnouncementController extends Controller
{
    public function index()
    {
        $announcements = Announcement::published()
            ->orderByDesc('published_at')
            ->orderByDesc('id')
            ->paginate(10);

        return view('announcements.index', compact('announcements'));
    }

    public function show(Announcement $announcement)
    {
        abort_unless($announcement->is_published, 404);

        // si published_at est dans le futur -> 404
        if ($announcement->published_at && $announcement->published_at->isFuture()) {
            abort(404);
        }

        return view('announcements.show', compact('announcement'));
    }
}
