<?php

namespace App\Http\Controllers;

use App\Models\News;
use Illuminate\Http\Request;

class AdminNewsController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->query('status');

        $newsList = News::query()
            ->when($status === 'draft', fn ($q) => $q->where('is_published', false))
            ->when($status === 'published', fn ($q) => $q->where('is_published', true))
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        return view('admin.news.index', ['newsList' => $newsList, 'status' => $status]);
    }

    public function create()
    {
        return view('admin.news.create');
    }

    public function store(Request $request)
    {
        $data = $this->validateData($request);

        News::create($data);

        return redirect()->route('admin.news.index')->with('success', 'Actualité créée.');
    }

    public function edit(News $news)
    {
        return view('admin.news.edit', compact('news'));
    }

    public function update(Request $request, News $news)
    {
        $data = $this->validateData($request, $news);

        $news->update($data);

        return redirect()->route('admin.news.index')->with('success', 'Actualité mise à jour.');
    }

    public function destroy(News $news)
    {
        if (!session('is_admin')) {
            abort(403, 'Action réservée aux administrateurs.');
        }

        $news->delete();

        return back()->with('success', 'Actualité supprimée.');
    }

    public function toggle(News $news)
    {
        $news->is_published = !$news->is_published;

        if ($news->is_published && !$news->published_at) {
            $news->published_at = now();
        }

        $news->save();

        return back()->with('success', $news->is_published ? 'Actualité publiée.' : 'Actualité repassée en brouillon.');
    }

    private function validateData(Request $request, ?News $news = null): array
    {
        $data = $request->validate([
            'title'        => ['required', 'string', 'max:255'],
            'resume'       => ['required', 'string'],
            'source_name'  => ['nullable', 'string', 'max:255'],
            'source_url'   => ['nullable', 'string', 'max:2048', 'url', 'unique:news,source_url' . ($news ? ',' . $news->id : '')],
            'impact'       => ['nullable', 'string', 'in:Faible,Moyen,Élevé'],
            'categorie'    => ['nullable', 'string', 'max:255'],
            'societes'     => ['nullable', 'string'],
            'mots_cles'    => ['nullable', 'string'],
            'is_published' => ['nullable', 'boolean'],
            'published_at' => ['nullable', 'date'],
        ]);

        $data['is_published'] = (bool) ($data['is_published'] ?? false);
        $data['societes']     = $this->linesToArray($data['societes'] ?? null);
        $data['mots_cles']    = $this->linesToArray($data['mots_cles'] ?? null);

        if ($data['is_published'] && empty($data['published_at'])) {
            $data['published_at'] = now();
        }

        return $data;
    }

    private function linesToArray(?string $value): ?array
    {
        if (!$value) {
            return null;
        }

        $items = array_filter(array_map('trim', explode("\n", $value)), fn ($v) => $v !== '');

        return $items ? array_values($items) : null;
    }
}
