<?php

namespace App\Http\Controllers;

use App\Models\Announcement;
use Illuminate\Http\Request;

class LandingController extends Controller
{
    public function index()
    {
        $annonces = Announcement::published()
            ->orderByDesc('published_at')
            ->orderByDesc('created_at')
            ->limit(3)
            ->get();

        return view('welcome', [
            'annonces' => $annonces,              // ✅ variable que la vue utilise souvent
            'exampleVideoUrl' => null,            // ✅ tu gardes ton mock video
        ]);
    }
}
