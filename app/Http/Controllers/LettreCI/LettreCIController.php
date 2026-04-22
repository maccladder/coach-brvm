<?php

namespace App\Http\Controllers\LettreCI;

use App\Http\Controllers\Controller;
use App\Models\LettreCIAccess;
use App\Models\LettreGenerated;
use Illuminate\Http\Request;

class LettreCIController extends Controller
{
    public function showcase()
    {
        $categories = config('lettreci_templates.categories');
        $types      = config('lettreci_templates.types');
        $price      = config('services.lettreci.price');

        return view('lettre-ci.marketplace-showcase', compact('categories', 'types', 'price'));
    }

    public function landing(Request $request)
    {
        $user = $request->user();

        if (LettreCIAccess::hasActiveAccess($user)) {
            return redirect()->route('lettreci.dashboard');
        }

        $price = config('services.lettreci.price');

        return view('lettre-ci.landing', compact('price'));
    }

    public function dashboard(Request $request)
    {
        $user  = $request->user();
        $count = LettreGenerated::where('user_id', $user->id)->count();
        $recent = LettreGenerated::where('user_id', $user->id)
            ->latest()
            ->take(5)
            ->get();

        return view('lettre-ci.dashboard', compact('count', 'recent'));
    }

    public function history(Request $request)
    {
        $letters = LettreGenerated::where('user_id', $request->user()->id)
            ->latest()
            ->paginate(15);

        return view('lettre-ci.history', compact('letters'));
    }
}
