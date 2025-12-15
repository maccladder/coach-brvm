<?php

namespace App\Http\Controllers;

use App\Models\SGI;
use Illuminate\Http\Request;

class SGIController extends Controller
{
    // Liste + filtres
    public function index(Request $request)
    {
        $query = SGI::query()->where('is_active', true);

        if ($request->filled('country')) {
            $query->where('country', $request->string('country'));
        }

        if ($request->filled('q')) {
            $q = $request->string('q');
            $query->where(function ($sub) use ($q) {
                $sub->where('name', 'like', "%{$q}%")
                    ->orWhere('city', 'like', "%{$q}%")
                    ->orWhere('email', 'like', "%{$q}%");
            });
        }

        $sgis = $query->orderBy('country')->orderBy('name')->paginate(24)->withQueryString();

        // Pour construire les onglets pays facilement
        $countries = SGI::query()
            ->where('is_active', true)
            ->select('country')
            ->distinct()
            ->orderBy('country')
            ->pluck('country');

        return view('sgis.index', compact('sgis', 'countries'));
    }

    // Fiche SGI
    public function show(string $slug)
    {
        $sgi = SGI::where('slug', $slug)->where('is_active', true)->firstOrFail();
        return view('sgis.show', compact('sgi'));
    }
}
