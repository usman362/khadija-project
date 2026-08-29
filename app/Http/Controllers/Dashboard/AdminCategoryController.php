<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class AdminCategoryController extends Controller
{
    public function index(Request $request): View
    {
        $query = Category::query()->withCount(['events', 'children']);

        if ($request->filled('search')) {
            $search = $request->string('search')->toString();
            $query->where('name', 'like', "%{$search}%");
        }

        if ($request->filled('status')) {
            if ($request->status === 'active') {
                $query->where('is_active', true);
            } elseif ($request->status === 'inactive') {
                $query->where('is_active', false);
            }
        }

        if ($request->filled('type')) {
            if ($request->type === 'parent') {
                $query->whereNull('parent_id');
            } elseif ($request->type === 'sub') {
                $query->whereNotNull('parent_id');
            }
        }

        // Card grid categories (paginated) — newest first, matching the legacy admin.
        $categories = $query->with('parent:id,name')->orderByDesc('sort_order')->orderByDesc('id')->paginate(12)->withQueryString();

        // Tree structure for sidebar (unlimited depth) — alphabetical, matching the legacy admin.
        $treeCategories = Category::whereNull('parent_id')
            ->with('allChildren')
            ->orderBy('name')
            ->get();

        $stats = [
            'total' => Category::count(),
            'active' => Category::where('is_active', true)->count(),
            'inactive' => Category::where('is_active', false)->count(),
            'parents' => Category::whereNull('parent_id')->count(),
            'subcategories' => Category::whereNotNull('parent_id')->count(),
        ];

        return view('dashboard.admin.categories.index', [
            'categories' => $categories,
            'treeCategories' => $treeCategories,
            'stats' => $stats,
            'filters' => $request->only(['search', 'status', 'type']),
        ]);
    }

    public function show(Category $category): View
    {
        $category->load('parent:id,name', 'children:id,parent_id,name,is_active');

        $eventsCount = $category->events()->count();

        return view('dashboard.admin.categories.show', [
            'category' => $category,
            'stats' => [
                'gigs'          => 0, // no gig subsystem yet
                'events'        => $eventsCount,
                'subcategories' => $category->children->count(),
            ],
        ]);
    }

    public function create(): View
    {
        return view('dashboard.admin.categories.create', [
            'parentCategories' => Category::getNestedDropdownList(),
        ]);
    }

    public function store(Request $request, \App\Services\ImagePipelineService $pipeline): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'short_description' => ['nullable', 'string', 'max:500'],
            'long_description' => ['nullable', 'string'],
            'cover_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'thumbnail' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:1024'],
            'icon' => ['nullable', 'string', 'max:100'],
            'parent_id' => ['nullable', 'exists:categories,id'],
            'is_active' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'insurance_requirement' => ['nullable', 'in:required,conditional,not_required'],
            'insurance_type' => ['nullable', 'string', 'max:80'],
            'insurance_tier' => ['nullable', 'in:A,B,C'],
        ]);

        $data = [
            'name' => $validated['name'],
            // Readable and unique, with a counted suffix only on a real
            // collision. It used to be name . Str::random(4) every time, which
            // made "Bridal Shower" live at /category/bridal-shower-9mKC.
            'slug' => Category::makeSlug($validated['name']),
            'short_description' => $validated['short_description'] ?? null,
            'long_description' => $validated['long_description'] ?? null,
            'icon' => $validated['icon'] ?? null,
            'parent_id' => $validated['parent_id'] ?? null,
            'is_active' => $request->boolean('is_active'),
            'sort_order' => $validated['sort_order'] ?? 0,
            'insurance_requirement' => $validated['insurance_requirement'] ?? null,
            'insurance_type' => $validated['insurance_type'] ?? null,
            'insurance_tier' => $validated['insurance_tier'] ?? null,
        ];

        /*
         * Every category picture is stored at one size — Sir Peter, 29 Aug.
         *
         * These used to be stored exactly as uploaded, at whatever dimensions
         * the file happened to have, so the event-type wall mixed 300x300
         * with anything else. Both columns go through the same crop because
         * `Category::imageUrl()` treats them interchangeably: a 300x300
         * thumbnail beside a 1600x500 cover would show the drift straight back.
         *
         * It crops rather than refuses — see the service for why.
         */
        foreach (['cover_image' => 'categories/covers', 'thumbnail' => 'categories/thumbnails'] as $field => $dir) {
            if (! $request->hasFile($field)) {
                continue;
            }

            $stored = $pipeline->squareForCategory($request->file($field), $dir);

            // An unreadable image is not a reason to lose the rest of the form;
            // the old picture stays and nothing is silently replaced.
            if ($stored) {
                $data[$field] = $stored;
            }
        }

        Category::create($data);

        return redirect()->route('app.admin.categories.index')->with('status', 'Category created successfully.');
    }

    public function edit(Category $category): View
    {
        return view('dashboard.admin.categories.edit', [
            'category' => $category,
            'parentCategories' => Category::getNestedDropdownList($category->id),
        ]);
    }

    public function update(Request $request, Category $category, \App\Services\ImagePipelineService $pipeline): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'short_description' => ['nullable', 'string', 'max:500'],
            'long_description' => ['nullable', 'string'],
            'cover_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'thumbnail' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:1024'],
            'icon' => ['nullable', 'string', 'max:100'],
            'parent_id' => ['nullable', 'exists:categories,id'],
            'is_active' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'insurance_requirement' => ['nullable', 'in:required,conditional,not_required'],
            'insurance_type' => ['nullable', 'string', 'max:80'],
            'insurance_tier' => ['nullable', 'in:A,B,C'],
        ]);

        $data = [
            'name' => $validated['name'],
            // The slug is NOT rebuilt here. Editing a description must not
            // change a live URL -- that is what broke every /category/{slug}
            // link on the site, one admin save at a time. A category keeps the
            // address it was published at.
            'short_description' => $validated['short_description'] ?? null,
            'long_description' => $validated['long_description'] ?? null,
            'icon' => $validated['icon'] ?? null,
            'parent_id' => $validated['parent_id'] ?? null,
            'is_active' => $request->boolean('is_active'),
            'sort_order' => $validated['sort_order'] ?? 0,
            'insurance_requirement' => $validated['insurance_requirement'] ?? null,
            'insurance_type' => $validated['insurance_type'] ?? null,
            'insurance_tier' => $validated['insurance_tier'] ?? null,
        ];

        /*
         * Every category picture is stored at one size — Sir Peter, 29 Aug.
         *
         * These used to be stored exactly as uploaded, at whatever dimensions
         * the file happened to have, so the event-type wall mixed 300x300
         * with anything else. Both columns go through the same crop because
         * `Category::imageUrl()` treats them interchangeably: a 300x300
         * thumbnail beside a 1600x500 cover would show the drift straight back.
         *
         * It crops rather than refuses — see the service for why.
         */
        foreach (['cover_image' => 'categories/covers', 'thumbnail' => 'categories/thumbnails'] as $field => $dir) {
            if (! $request->hasFile($field)) {
                continue;
            }

            $stored = $pipeline->squareForCategory($request->file($field), $dir);

            // An unreadable image is not a reason to lose the rest of the form;
            // the old picture stays and nothing is silently replaced.
            if ($stored) {
                $data[$field] = $stored;
            }
        }

        $category->update($data);

        return redirect()->route('app.admin.categories.index')->with('status', 'Category updated successfully.');
    }

    public function destroy(Category $category): RedirectResponse
    {
        $category->children()->update(['parent_id' => null]);
        $category->events()->detach();
        $category->delete();

        return redirect()->route('app.admin.categories.index')->with('status', 'Category deleted successfully.');
    }
}
