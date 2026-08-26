<?php

namespace App\Http\Controllers;

use Log;
use App\Models\User;
use App\Mail\AdminBroadcastMail;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;

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
            'free_emails' => ['nullable', 'string', 'max:5000'],
            'subject' => ['required', 'string', 'max:200'],
            'message' => ['required', 'string', 'max:10000'],
        ]);

        $subject = $request->subject;
        $safeHtml = $this->sanitizeHtml($request->message);

        $freeEmails = $this->parseFreeEmails((string) $request->input('free_emails', ''));

        if (count($freeEmails) > 50) {
            return back()->with('error', 'Maximum 50 adresses libres par envoi (' . count($freeEmails) . ' fournies).');
        }

        $invalid = array_filter($freeEmails, function ($addr) {
            return Validator::make(['email' => $addr], ['email' => 'email'])->fails();
        });

        if (count($invalid) > 0) {
            return back()->with('error', 'Adresse(s) invalide(s) : ' . implode(', ', $invalid));
        }

        $query = User::query();

        if ($request->mode === 'selected') {
            $ids = $request->user_ids ?? [];
            if (count($ids) === 0 && count($freeEmails) === 0) {
                return back()->with('error', 'Sélectionne au moins un utilisateur ou une adresse libre.');
            }
            $query->whereIn('id', $ids);
        }

        $users = $query->select('id', 'name', 'email')->get();

        // Fusion utilisateurs + adresses libres, dédoublonnée par email (insensible à la casse)
        $recipients = [];
        foreach ($users as $user) {
            $recipients[strtolower($user->email)] = ['email' => $user->email, 'name' => $user->name];
        }
        foreach ($freeEmails as $addr) {
            $key = strtolower($addr);
            if (!isset($recipients[$key])) {
                $recipients[$key] = ['email' => $addr, 'name' => null];
            }
        }

        if (count($recipients) === 0) {
            return back()->with('error', 'Aucun destinataire.');
        }

        $sent = 0;
        $failed = 0;

        foreach ($recipients as $r) {
            try {
                Mail::to($r['email'], $r['name'])
                    ->send(new AdminBroadcastMail($subject, $safeHtml, $r['name'] ?? $r['email']));
                $sent++;
            } catch (\Throwable $e) {
                $failed++;
                Log::error('Admin email failed', [
                    'email' => $r['email'],
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return back()->with('success', "Emails envoyés: {$sent}. Échecs: {$failed}.");
    }

    /**
     * Découpe le champ "adresses libres" (une par ligne, ou séparées par
     * virgule/point-virgule) et dédoublonne (insensible à la casse).
     */
    private function parseFreeEmails(string $raw): array
    {
        $parts = preg_split('/[\r\n,;]+/', $raw) ?: [];

        $emails = [];
        foreach ($parts as $addr) {
            $addr = trim($addr);
            if ($addr === '') {
                continue;
            }
            $key = strtolower($addr);
            if (!isset($emails[$key])) {
                $emails[$key] = $addr;
            }
        }

        return array_values($emails);
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
