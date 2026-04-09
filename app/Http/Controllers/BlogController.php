<?php

namespace App\Http\Controllers;

use App\Models\BlogCategory;
use App\Models\BlogPost;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BlogController extends Controller
{
    /**
     * Public blog listing page with optional category filter and search.
     */
    public function index(Request $request): View
    {
        $query = BlogPost::published()
            ->with(['category:id,name,slug', 'author:id,name'])
            ->orderByDesc('published_at');

        if ($request->filled('category')) {
            $category = BlogCategory::where('slug', $request->input('category'))->first();
            if ($category) {
                $query->where('blog_category_id', $category->id);
            }
        }

        if ($request->filled('q')) {
            $s = $request->input('q');
            $query->where(function ($q) use ($s) {
                $q->where('title', 'like', "%{$s}%")
                  ->orWhere('excerpt', 'like', "%{$s}%");
            });
        }

        $posts      = $query->paginate(9)->withQueryString();
        $categories = BlogCategory::active()
            ->withCount(['posts' => fn($q) => $q->published()])
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $featured = BlogPost::published()
            ->with(['category:id,name,slug'])
            ->orderByDesc('views_count')
            ->take(3)
            ->get();

        return view('blog.index', compact('posts', 'categories', 'featured'));
    }

    /**
     * Public blog post detail page.
     */
    public function show(BlogPost $post): View
    {
        abort_unless($post->isPublished(), 404);

        $post->loadMissing(['category:id,name,slug', 'author:id,name,avatar']);

        // Increment view count (non-blocking)
        $post->increment('views_count');

        // Related posts from the same category
        $related = BlogPost::published()
            ->where('id', '!=', $post->id)
            ->when($post->blog_category_id, fn($q) => $q->where('blog_category_id', $post->blog_category_id))
            ->with(['category:id,name,slug'])
            ->orderByDesc('published_at')
            ->take(3)
            ->get();

        return view('blog.show', compact('post', 'related'));
    }
}
