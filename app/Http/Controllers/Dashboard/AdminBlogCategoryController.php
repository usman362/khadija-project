<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\BlogCategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminBlogCategoryController extends Controller
{
    public function index(): View
    {
        $categories = BlogCategory::withCount('posts')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->paginate(20);

        return view('dashboard.admin.blog.categories.index', compact('categories'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name'        => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:500'],
            'is_active'   => ['nullable', 'boolean'],
            'sort_order'  => ['nullable', 'integer', 'min:0', 'max:9999'],
        ]);

        BlogCategory::create([
            'name'        => $validated['name'],
            'description' => $validated['description'] ?? null,
            'is_active'   => (bool) ($validated['is_active'] ?? true),
            'sort_order'  => $validated['sort_order'] ?? 0,
        ]);

        return back()->with('status', 'Blog category created successfully.');
    }

    public function update(Request $request, BlogCategory $category): RedirectResponse
    {
        $validated = $request->validate([
            'name'        => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:500'],
            'is_active'   => ['nullable', 'boolean'],
            'sort_order'  => ['nullable', 'integer', 'min:0', 'max:9999'],
        ]);

        $category->update([
            'name'        => $validated['name'],
            'description' => $validated['description'] ?? null,
            'is_active'   => (bool) ($validated['is_active'] ?? false),
            'sort_order'  => $validated['sort_order'] ?? 0,
        ]);

        return back()->with('status', 'Blog category updated successfully.');
    }

    public function destroy(BlogCategory $category): RedirectResponse
    {
        $category->delete();
        return back()->with('status', 'Blog category deleted.');
    }
}
