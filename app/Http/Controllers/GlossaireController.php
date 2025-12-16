<?php

namespace App\Http\Controllers;

use App\Models\Glossaire;
use Illuminate\Http\Request;

class GlossaireController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->q;

        $items = Glossaire::when($search, function ($q) use ($search) {
                $q->where('terme', 'like', "%{$search}%");
            })
            ->orderBy('lettre')
            ->orderBy('terme')
            ->get()
            ->groupBy('lettre');

        return view('sections.glossaire', compact('items', 'search'));
    }
}
