<?php

namespace App\Http\Controllers;

use App\Models\StagiaireLog;
use Illuminate\Http\Request;

class StagiaireController extends Controller
{
    public function showLoginForm()
    {
        return view('stagiaire.login');
    }

    public function login(Request $request)
    {
        $request->validate([
            'code' => ['required', 'string'],
        ]);

        // Code dans .env (STAGIAIRE_CODE), lu via la config : env() direct renvoie null quand la
        // config est en cache, et l'ancienne valeur par défaut était publique dans le dépôt
        $expected = (string) config('services.admin_panel.stagiaire_code');

        if ($expected === '') {
            return back()->with('error', "Accès stagiaire non configuré (STAGIAIRE_CODE manquant dans .env).");
        }

        if (!hash_equals($expected, (string) $request->code)) {
            return back()
                ->withInput()
                ->with('error', 'Code incorrect.');
        }

        $request->session()->regenerate();
        session(['is_stagiaire' => true]);

        StagiaireLog::record('login', 'Connexion au panel stagiaire', 'stagiaire.login', 'POST');

        return redirect()->route('admin.dashboard');
    }

    public function logout(Request $request)
    {
        StagiaireLog::record('logout', 'Déconnexion du panel stagiaire', 'stagiaire.logout', 'POST');

        $request->session()->forget('is_stagiaire');

        return redirect()->route('stagiaire.login.form')
            ->with('success', 'Déconnecté avec succès.');
    }
}
