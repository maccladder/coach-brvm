<?php

namespace App\Http\Controllers;

class ComparateurController extends Controller
{
    public function index()
    {
        return view('comparateur');
    }

    public function data()
    {
        $path = storage_path('app/comparateur/produits.json');

        if (!file_exists($path)) {
            abort(404);
        }

        return response(file_get_contents($path), 200, [
            'Content-Type'  => 'application/json',
            'Cache-Control' => 'no-store',
        ]);
    }
}
