<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\BlogCategory;
use App\Models\BlogPost;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class AdminBlogPostController extends Controller
{
    public function index(Request $request): View
    {
        $query = BlogPost::with(['category:id,name', 'author:id,name'])
            ->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('category')) {
            $query->where('blog_category_id', $request->input('category'));
        }

        if ($request->filled('search')) {
            $s = $request->input('search');
            $query->where(function ($q) use ($s) {
                $q->where('title', 'like', "%{$s}%")
                  ->orWhere('excerpt', 'like', "%{$s}%");
            });
        }

        $posts = $query->paginate(15)->withQueryString();

        $stats = [
            'total'     => BlogPost::count(),
            'published' => BlogPost::where('status', BlogPost::STATUS_PUBLISHED)->count(),
            'draft'     => BlogPost::where('status', BlogPost::STATUS_DRAFT)->count(),
            'archived'  => BlogPost::where('status', BlogPost::STATUS_ARCHIVED)->count(),
        ];

        $categories = BlogCategory::orderBy('name')->get();

        return view('dashboard.admin.blog.posts.index', compact('posts', 'stats', 'categories'));
    }

    public function create(): View
    {
        $categories = BlogCategory::active()->orderBy('name')->get();
        return view('dashboard.admin.blog.posts.create', compact('categories'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validatePost($request);

        $data = $this->prepareData($request, $validated);
        $data['author_id'] = $request->user()->id;

        $post = BlogPost::create($data);

        return redirect()->route('app.admin.blog.posts.edit', $post)
            ->with('status', 'Blog post created successfully.');
    }

    public function edit(BlogPost $post): View
    {
        $categories = BlogCategory::active()->orderBy('name')->get();
        return view('dashboard.admin.blog.posts.edit', compact('post', 'categories'));
    }

    public function update(Request $request, BlogPost $post): RedirectResponse
    {
        $validated = $this->validatePost($request, $post->id);

        $data = $this->prepareData($request, $validated, $post);

        $post->update($data);

        return back()->with('status', 'Blog post updated successfully.');
    }

    public function destroy(BlogPost $post): RedirectResponse
    {
        if ($post->featured_image) {
            Storage::disk('public')->delete($post->featured_image);
        }

        $post->delete();

        return redirect()->route('app.admin.blog.posts.index')
            ->with('status', 'Blog post deleted.');
    }

    // ── Helpers ────────────────────────────────────

    private function validatePost(Request $request, ?int $ignoreId = null): array
    {
        return $request->validate([
            'blog_category_id' => ['nullable', 'exists:blog_categories,id'],
            'title'            => ['required', 'string', 'max:255'],
            'slug'             => ['nullable', 'string', 'max:255'],
            'excerpt'          => ['nullable', 'string', 'max:500'],
            'content'          => ['required', 'string'],
            'featured_image'   => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            'meta_title'       => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string', 'max:300'],
            'status'           => ['required', 'in:draft,published,archived'],
            'published_at'     => ['nullable', 'date'],
            'remove_image'     => ['nullable', 'boolean'],
        ]);
    }

    private function prepareData(Request $request, array $validated, ?BlogPost $post = null): array
    {
        $data = [
            'blog_category_id' => $validated['blog_category_id'] ?? null,
            'title'            => $validated['title'],
            'excerpt'          => $validated['excerpt'] ?? null,
            'content'          => $validated['content'],
            'meta_title'       => $validated['meta_title'] ?? null,
            'meta_description' => $validated['meta_description'] ?? null,
            'status'           => $validated['status'],
            'published_at'     => $this->resolvePublishedAt($validated),
        ];

        // Slug handling — allow manual override, otherwise let the model auto-generate
        if (!empty($validated['slug'])) {
            $data['slug'] = BlogPost::uniqueSlug($validated['slug'], $post?->id);
        }

        // Featured image upload / removal
        if ($request->boolean('remove_image') && $post?->featured_image) {
            Storage::disk('public')->delete($post->featured_image);
            $data['featured_image'] = null;
        }

        if ($request->hasFile('featured_image')) {
            if ($post?->featured_image) {
                Storage::disk('public')->delete($post->featured_image);
            }
            $data['featured_image'] = $request->file('featured_image')->store('blog', 'public');
        }

        return $data;
    }

    private function resolvePublishedAt(array $validated): ?\Illuminate\Support\Carbon
    {
        if ($validated['status'] !== BlogPost::STATUS_PUBLISHED) {
            return null;
        }

        return !empty($validated['published_at'])
            ? \Illuminate\Support\Carbon::parse($validated['published_at'])
            : now();
    }
}
