<?php

namespace App\Http\Controllers;

use Log;
use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Mail;

class AdminEmailController extends Controller
{
    public function index(Request $request)
    {
        $q = trim((string) $request->get('q'));

        $users = User::query()
            ->when($q, function ($query) use ($q) {
                $query->where('name', 'like', "%{$q}%")
                      ->orWhere('email', 'like', "%{$q}%");
            })
            ->orderByDesc('created_at')
            ->paginate(30)
            ->withQueryString();

        return view('admin.emails.index', compact('users', 'q'));
    }

    public function send(Request $request)
    {
        $request->validate([
            'mode' => ['required', 'in:all,selected'],
            'user_ids' => ['array'],
            'user_ids.*' => ['integer', 'exists:users,id'],
            'subject' => ['required', 'string', 'max:200'],
            'message' => ['required', 'string', 'max:10000'],
        ]);

        $subject = $request->subject;
        $message = $request->message;

        $query = User::query();

        if ($request->mode === 'selected') {
            $ids = $request->user_ids ?? [];
            if (count($ids) === 0) {
                return back()->with('error', 'Sélectionne au moins un utilisateur.');
            }
            $query->whereIn('id', $ids);
        }

        $users = $query->select('id','name','email')->get();

        $sent = 0;
        $failed = 0;

        foreach ($users as $user) {
            try {
                Mail::raw($message, function ($m) use ($user, $subject) {
                    $m->to($user->email, $user->name)->subject($subject);
                });
                $sent++;
            } catch (\Throwable $e) {
                $failed++;
                // option: logger l’erreur
                Log::error('Admin email failed', [
                    'user_id' => $user->id,
                    'email' => $user->email,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return back()->with('success', "Emails envoyés: {$sent}. Échecs: {$failed}.");
    }
}
