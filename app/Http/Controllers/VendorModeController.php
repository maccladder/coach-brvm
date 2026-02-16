<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class VendorModeController extends Controller
{
    // ✅ Activer le statut vendeur (1 seul compte)
    public function becomeVendor(Request $request)
    {
        $user = $request->user();

        if (!$user->is_vendor) {
            $user->is_vendor = true;
            $user->vendor_status = $user->vendor_status === 'none' ? 'approved' : $user->vendor_status;
            $user->save();
        }

        // On bascule direct en mode vendeur
        session(['view_mode' => 'vendor']);

        return redirect()->route('vendor.dashboard')
            ->with('success', 'Bienvenue ! Vous êtes maintenant en mode vendeur.');
    }

    // ✅ Passer en mode vendeur
    public function switchToVendor(Request $request)
    {
        abort_unless($request->user()->is_vendor, 403);

        session(['view_mode' => 'vendor']);

        return redirect()->route('vendor.dashboard');
    }

    // ✅ Revenir en mode utilisateur
    public function switchToUser(Request $request)
    {
        session(['view_mode' => 'user']);

        // Choisis ta page de retour (dashboard user)
        return redirect()->route('dashboard');
    }
}
