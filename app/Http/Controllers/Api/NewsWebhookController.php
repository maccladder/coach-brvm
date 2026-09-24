<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\News;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class NewsWebhookController extends Controller
{
    /**
     * Reçoit un article du workflow n8n « Rédacteur en Chef » et le publie
     * immédiatement. Une URL déjà connue (publiée ou non) n'est jamais
     * recréée ni modifiée : un article dépublié par l'admin le reste.
     */
    public function store(Request $request)
    {
        // Validator manuel (au lieu de $request->validate()) pour garantir une
        // réponse JSON même quand l'appelant n'envoie pas d'en-tête Accept adapté
        // (sinon Laravel redirige en HTML sur échec de validation).
        $validator = Validator::make($request->all(), [
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

        if ($validator->fails()) {
            return response()->json([
                'error'  => 'validation_failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = $validator->validated();

        $existing = News::where('source_url', $data['url'])->first();
        if ($existing) {
            return $this->doublon($existing, $data['url']);
        }

        try {
            $news = News::create([
                'title'        => $data['titre'],
                'resume'       => $data['resume'] ?? '',
                'source_name'  => $data['source'] ?? null,
                'source_url'   => $data['url'],
                'impact'       => $data['impact'] ?? null,
                'categorie'    => $data['categorie'] ?? null,
                'societes'     => $data['societes'] ?? null,
                'mots_cles'    => $data['mots_cles'] ?? null,
                'is_published' => true,
                'published_at' => now(),
            ]);
        } catch (UniqueConstraintViolationException $e) {
            // Envoi simultané de la même URL : l'index unique news_source_url_unique
            // a arbitré, l'autre requête a créé l'article.
            $existing = News::where('source_url', $data['url'])->first();

            if (!$existing) {
                throw $e; // violation d'un autre index (slug) : erreur réelle
            }

            return $this->doublon($existing, $data['url']);
        }

        Log::info('News webhook n8n — publiée', [
            'source_url' => $data['url'],
            'id'         => $news->id,
        ]);

        return response()->json(['status' => 'publie', 'id' => $news->id], 201);
    }

    private function doublon(News $news, string $url)
    {
        Log::info('News webhook n8n — doublon', ['source_url' => $url, 'id' => $news->id]);

        return response()->json(['status' => 'doublon', 'id' => $news->id], 200);
    }
}
