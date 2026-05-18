<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\PackBrvmConfirmationMail;
use App\Models\Course;
use App\Models\PackWhatsappToken;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class AdminPackController extends Controller
{
    private const COURSE_SLUGS = [
        'brvm-debutant',
        'brvm-intermediaire',
        'brvm-pratique-outils-analyse-portefeuille-virtuel',
    ];

    private function packCourses()
    {
        return Course::whereIn('slug', self::COURSE_SLUGS)->get();
    }

    public function index()
    {
        $payments = Payment::where('purpose', 'pack_brvm')
            ->where('status', 'ACCEPTED')
            ->with('user')
            ->latest('credited_at')
            ->paginate(30);

        $userIds      = $payments->pluck('user_id')->unique()->all();
        $latestTokens = PackWhatsappToken::whereIn('user_id', $userIds)
            ->orderByDesc('created_at')
            ->get()
            ->groupBy('user_id')
            ->map(fn($tokens) => $tokens->first());

        return view('admin.packs.index', compact('payments', 'latestTokens'));
    }

    public function resend(User $user)
    {
        $token = Str::random(64);

        // Nouveau token — l'ancien est conservé en base pour l'audit
        PackWhatsappToken::create([
            'user_id' => $user->id,
            'token'   => $token,
        ]);

        $courses = $this->packCourses();
        Mail::to($user->email)->send(new PackBrvmConfirmationMail($user, $token, $courses));

        return back()->with('success', "Email renvoyé à {$user->name} avec un nouveau lien.");
    }

    public function resetToken(PackWhatsappToken $packToken)
    {
        $userName = $packToken->user->name ?? 'cet utilisateur';

        $packToken->used    = false;
        $packToken->used_at = null;
        $packToken->save();

        return back()->with('success', "Token réinitialisé pour {$userName}.");
    }
}
