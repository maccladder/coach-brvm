<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\News;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class NewsWebhookController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->validate([
            'titre'      => ['required', 'string', 'max:255'],
            'url'        => ['required', 'string', 'max:2048', 'url'],
            'source'     => ['nullable', 'string', 'max:255'],
            'resume'     => ['nullable', 'string'],
            'impact'     => ['nullable', 'string', 'in:Faible,Moyen,Élevé'],
            'categorie'  => ['nullable', 'string', 'max:255'],
            'societes'   => ['nullable', 'array'],
            'societes.*' => ['string', 'max:255'],
            'mots_cles'   => ['nullable', 'array'],
            'mots_cles.*' => ['string', 'max:255'],
        ]);

        $news = News::firstOrCreate(
            ['source_url' => $data['url']],
            [
                'title'        => $data['titre'],
                'resume'       => $data['resume'] ?? '',
                'source_name'  => $data['source'] ?? null,
                'source_url'   => $data['url'],
                'impact'       => $data['impact'] ?? null,
                'categorie'    => $data['categorie'] ?? null,
                'societes'     => $data['societes'] ?? null,
                'mots_cles'    => $data['mots_cles'] ?? null,
                'is_published' => false,
                'published_at' => null,
            ]
        );

        Log::info('News webhook n8n', [
            'source_url' => $data['url'],
            'created'    => $news->wasRecentlyCreated,
            'id'         => $news->id,
        ]);

        return response()->json([
            'created' => $news->wasRecentlyCreated,
            'id'      => $news->id,
        ], $news->wasRecentlyCreated ? 201 : 200);
    }
}
