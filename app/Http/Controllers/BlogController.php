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
        $activeCategory = $request->input('category');
        $search         = trim((string) $request->input('q', ''));
        $sort           = $request->input('sort', 'latest');
        $isFiltering    = $activeCategory || $search !== '';

        // Editorial hero — most-viewed published post. Only shown on the
        // default (unfiltered) view so search/category results read cleanly.
        $featured = $isFiltering ? null : BlogPost::published()
            ->with(['category:id,name,slug'])
            ->orderByDesc('views_count')
            ->orderByDesc('published_at')
            ->first();

        $query = BlogPost::published()
            ->with(['category:id,name,slug', 'author:id,name']);

        match ($sort) {
            'oldest'  => $query->orderBy('published_at'),
            'popular' => $query->orderByDesc('views_count')->orderByDesc('published_at'),
            default   => $query->orderByDesc('published_at'),
        };

        if ($featured) {
            $query->where('id', '!=', $featured->id);
        }

        if ($activeCategory) {
            $category = BlogCategory::where('slug', $activeCategory)->first();
            if ($category) {
                $query->where('blog_category_id', $category->id);
            }
        }

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('excerpt', 'like', "%{$search}%");
            });
        }

        $posts = $query->paginate(9)->withQueryString();

        $categories = BlogCategory::active()
            ->withCount(['posts' => fn($q) => $q->published()])
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return view('blog.index', compact('posts', 'categories', 'featured', 'activeCategory', 'search', 'sort'));
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
