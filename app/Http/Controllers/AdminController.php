<?php

namespace App\Http\Controllers;

use App\Models\ClientBoc;
use App\Models\ClientFinancial;
use Illuminate\Http\Request;

class AdminController extends Controller
{
    /** Page de login admin */
    public function showLoginForm()
    {
        return view('admin.login');
    }

    /** Vérification du code admin */
    public function login(Request $request)
    {
        $request->validate([
            'code' => ['required', 'string'],
        ]);

        if ($request->code !== 'Coach-brvm2025') {
            return back()
                ->withInput()
                ->with('error', 'Code incorrect.');
        }

        // Auth admin simple via session
        session(['is_admin' => true]);

        return redirect()->route('admin.dashboard');
    }

    /** Déconnexion admin */
    public function logout(Request $request)
    {
        $request->session()->forget('is_admin');

        return redirect()->route('admin.login.form')
            ->with('success', 'Déconnecté avec succès.');
    }

    /** Dashboard admin */
    public function dashboard()
    {
        $bocs = ClientBoc::orderByDesc('created_at')->limit(50)->get();
        $financials = ClientFinancial::orderByDesc('created_at')->limit(50)->get();

        return view('admin.dashboard', compact('bocs', 'financials'));
    }
}
