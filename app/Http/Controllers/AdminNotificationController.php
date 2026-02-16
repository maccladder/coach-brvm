<?php

namespace App\Http\Controllers;

use App\Models\AdminNotification;
use Illuminate\Http\Request;

class AdminNotificationController extends Controller
{
    public function index()
    {
        $notifications = AdminNotification::latest()->paginate(25);
        return view('admin.notifications.index', compact('notifications'));
    }

    public function read(int $id)
    {
        $n = AdminNotification::findOrFail($id);
        $n->update(['read_at' => now()]);

        return redirect($n->url ?: route('admin.notifications.index'));
    }

    public function readAll()
    {
        AdminNotification::whereNull('read_at')->update(['read_at' => now()]);
        return back()->with('success', '✅ Notifications admin marquées comme lues.');
    }
}
