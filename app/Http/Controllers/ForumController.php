<?php

namespace App\Http\Controllers;

use App\Models\ForumCategory;
use App\Models\ForumLike;
use App\Models\ForumPost;
use App\Models\ForumTopic;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ForumController extends Controller
{
    public function index()
    {
        $categories = ForumCategory::withCount(['topics', 'posts'])
            ->orderBy('order')
            ->get();

        $recentTopics = ForumTopic::with(['user', 'category'])
            ->withCount('posts')
            ->latest()
            ->limit(5)
            ->get();

        return view('forum.index', compact('categories', 'recentTopics'));
    }

    public function category(string $slug)
    {
        $category = ForumCategory::where('slug', $slug)->firstOrFail();

        $topics = ForumTopic::where('forum_category_id', $category->id)
            ->with(['user'])
            ->withCount('posts')
            ->orderByDesc('is_pinned')
            ->latest()
            ->paginate(20);

        return view('forum.category', compact('category', 'topics'));
    }

    public function topic(string $categorySlug, string $topicSlug)
    {
        $category = ForumCategory::where('slug', $categorySlug)->firstOrFail();

        $topic = ForumTopic::where('forum_category_id', $category->id)
            ->where('slug', $topicSlug)
            ->firstOrFail();

        $topic->increment('views');

        $posts = ForumPost::where('forum_topic_id', $topic->id)
            ->with(['user', 'likes'])
            ->withCount('likes')
            ->oldest()
            ->paginate(30);

        $userLikedPostIds = auth()->check()
            ? ForumLike::where('user_id', auth()->id())
                ->whereIn('forum_post_id', $posts->pluck('id'))
                ->pluck('forum_post_id')
            : collect();

        return view('forum.topic', compact('category', 'topic', 'posts', 'userLikedPostIds'));
    }

    public function createTopic(string $slug)
    {
        $category = ForumCategory::where('slug', $slug)->firstOrFail();

        return view('forum.create-topic', compact('category'));
    }

    public function storeTopic(Request $request, string $slug)
    {
        $category = ForumCategory::where('slug', $slug)->firstOrFail();

        $request->validate([
            'title' => 'required|string|max:200',
            'body'  => 'required|string|max:10000',
        ]);

        $base = Str::slug($request->title);
        $slug_candidate = $base;
        $i = 1;
        while (ForumTopic::where('slug', $slug_candidate)->exists()) {
            $slug_candidate = $base . '-' . $i++;
        }

        $topic = ForumTopic::create([
            'forum_category_id' => $category->id,
            'user_id'           => auth()->id(),
            'title'             => $request->title,
            'slug'              => $slug_candidate,
            'body'              => $request->body,
        ]);

        return redirect()
            ->route('forum.topic', [$category->slug, $topic->slug])
            ->with('success', 'Votre sujet a été publié.');
    }

    public function storePost(Request $request, string $categorySlug, string $topicSlug)
    {
        $category = ForumCategory::where('slug', $categorySlug)->firstOrFail();

        $topic = ForumTopic::where('forum_category_id', $category->id)
            ->where('slug', $topicSlug)
            ->firstOrFail();

        if ($topic->is_locked) {
            return back()->with('error', 'Ce sujet est verrouillé, les nouvelles réponses sont désactivées.');
        }

        $request->validate(['body' => 'required|string|max:5000']);

        ForumPost::create([
            'forum_topic_id' => $topic->id,
            'user_id'        => auth()->id(),
            'body'           => $request->body,
        ]);

        return redirect()
            ->route('forum.topic', [$categorySlug, $topicSlug])
            ->with('success', 'Votre réponse a été publiée.');
    }

    public function likePost(Request $request, ForumPost $post)
    {
        $userId   = auth()->id();
        $existing = ForumLike::where('forum_post_id', $post->id)
            ->where('user_id', $userId)
            ->first();

        if ($existing) {
            $existing->delete();
            $liked = false;
        } else {
            ForumLike::create(['forum_post_id' => $post->id, 'user_id' => $userId]);
            $liked = true;
        }

        $count = ForumLike::where('forum_post_id', $post->id)->count();

        if ($request->expectsJson()) {
            return response()->json(['liked' => $liked, 'count' => $count]);
        }

        return back();
    }
}
