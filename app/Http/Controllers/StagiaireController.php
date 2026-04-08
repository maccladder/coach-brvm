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

        if ($request->code !== env('STAGIAIRE_CODE', 'Stagiaire-brvm2025')) {
            return back()
                ->withInput()
                ->with('error', 'Code incorrect.');
        }

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
