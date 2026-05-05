<?php

namespace App\Http\Controllers;

use Log;
use App\Models\User;
use App\Mail\AdminBroadcastMail;
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
        $safeHtml = $this->sanitizeHtml($request->message);

        $query = User::query();

        if ($request->mode === 'selected') {
            $ids = $request->user_ids ?? [];
            if (count($ids) === 0) {
                return back()->with('error', 'Sélectionne au moins un utilisateur.');
            }
            $query->whereIn('id', $ids);
        }

        $users = $query->select('id', 'name', 'email')->get();

        $sent = 0;
        $failed = 0;

        foreach ($users as $user) {
            try {
                Mail::to($user->email, $user->name)
                    ->send(new AdminBroadcastMail($subject, $safeHtml, $user->name));
                $sent++;
            } catch (\Throwable $e) {
                $failed++;
                Log::error('Admin email failed', [
                    'user_id' => $user->id,
                    'email'   => $user->email,
                    'error'   => $e->getMessage(),
                ]);
            }
        }

        return back()->with('success', "Emails envoyés: {$sent}. Échecs: {$failed}.");
    }

    /**
     * Autorise un sous-ensemble de balises HTML sûres et supprime les attributs
     * d'événements (on*) et les URLs javascript: pour éviter les XSS.
     */
    private function sanitizeHtml(string $html): string
    {
        $allowed = '<p><br><strong><b><em><i><u><s><h1><h2><h3><h4>'
                 . '<a><ul><ol><li><div><span><blockquote><img><hr><table><thead><tbody><tr><th><td>';

        $html = strip_tags($html, $allowed);

        // Supprime les gestionnaires d'événements on* (ex: onclick, onerror)
        $html = preg_replace('/\bon\w+\s*=\s*"[^"]*"/i', '', $html);
        $html = preg_replace('/\bon\w+\s*=\s*\'[^\']*\'/i', '', $html);

        // Bloque les URLs javascript: dans href et src
        $html = preg_replace('/\b(href|src)\s*=\s*"javascript:[^"]*"/i', '$1="#"', $html);
        $html = preg_replace('/\b(href|src)\s*=\s*\'javascript:[^\']*\'/i', "$1='#'", $html);

        return $html;
    }
}
