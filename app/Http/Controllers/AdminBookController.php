<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Models\BookPage;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AdminBookController extends Controller
{
    public function index()
    {
        $books = Book::withCount('pages')->latest()->get();
        return view('admin.books.index', compact('books'));
    }

    public function create()
    {
        return view('admin.books.form', ['book' => new Book()]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'title'              => 'required|string|max:255',
            'description'        => 'nullable|string',
            'is_free'            => 'boolean',
            'is_published'       => 'boolean',
            'estimated_minutes'  => 'required|integer|min:1',
        ]);

        $data['slug']         = Str::slug($data['title']) . '-' . Str::random(5);
        $data['is_free']      = $request->boolean('is_free');
        $data['is_published'] = $request->boolean('is_published');

        $book = Book::create($data);

        return redirect()->route('admin.books.show', $book)
            ->with('success', 'Livre créé avec succès.');
    }

    public function show(Book $book)
    {
        $pages = $book->pages()->get();
        return view('admin.books.show', compact('book', 'pages'));
    }

    public function edit(Book $book)
    {
        return view('admin.books.form', compact('book'));
    }

    public function update(Request $request, Book $book)
    {
        $data = $request->validate([
            'title'             => 'required|string|max:255',
            'description'       => 'nullable|string',
            'is_free'           => 'boolean',
            'is_published'      => 'boolean',
            'estimated_minutes' => 'required|integer|min:1',
        ]);

        $data['is_free']      = $request->boolean('is_free');
        $data['is_published'] = $request->boolean('is_published');

        $book->update($data);

        return redirect()->route('admin.books.show', $book)
            ->with('success', 'Livre mis à jour.');
    }

    public function destroy(Book $book)
    {
        if (!session('is_admin')) abort(403);
        $book->delete();
        return redirect()->route('admin.books.index')
            ->with('success', 'Livre supprimé.');
    }

    // ── Pages ──────────────────────────────────────────────

    public function storePage(Request $request, Book $book)
    {
        $data = $request->validate([
            'page_no' => 'required|integer|min:1',
            'title'   => 'nullable|string|max:255',
            'content' => 'required|string',
        ]);

        $book->pages()->updateOrCreate(
            ['page_no' => $data['page_no']],
            ['title' => $data['title'] ?? null, 'content' => $data['content']]
        );

        return back()->with('success', "Page {$data['page_no']} enregistrée.");
    }

    public function destroyPage(Book $book, BookPage $page)
    {
        if (!session('is_admin')) abort(403);
        abort_unless($page->book_id === $book->id, 404);
        $page->delete();
        return back()->with('success', 'Page supprimée.');
    }
}
