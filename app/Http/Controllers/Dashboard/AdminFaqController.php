<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Faq;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminFaqController extends Controller
{
    public function index(Request $request): View
    {
        $query = Faq::query();

        if ($request->filled('search')) {
            $search = $request->string('search')->toString();
            $query->where(function ($q) use ($search) {
                $q->where('question', 'like', "%{$search}%")
                  ->orWhere('answer', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('is_active', $request->status === 'active');
        }

        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }

        $faqs = $query->ordered()->paginate(15)->withQueryString();

        $stats = [
            'total'    => Faq::count(),
            'active'   => Faq::where('is_active', true)->count(),
            'inactive' => Faq::where('is_active', false)->count(),
        ];

        $categories = Faq::whereNotNull('category')
            ->distinct()
            ->pluck('category')
            ->sort()
            ->values();

        return view('dashboard.admin.faqs.index', compact('faqs', 'stats', 'categories'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'question'   => ['required', 'string', 'max:500'],
            'answer'     => ['required', 'string'],
            'category'   => ['nullable', 'string', 'max:100'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active'  => ['nullable', 'boolean'],
        ]);

        Faq::create([
            'question'   => $validated['question'],
            'answer'     => $validated['answer'],
            'category'   => $validated['category'] ?? null,
            'sort_order' => $validated['sort_order'] ?? 0,
            'is_active'  => $request->boolean('is_active'),
        ]);

        return redirect()->route('app.admin.faqs.index')->with('status', 'FAQ created successfully.');
    }

    public function update(Request $request, Faq $faq): RedirectResponse
    {
        $validated = $request->validate([
            'question'   => ['required', 'string', 'max:500'],
            'answer'     => ['required', 'string'],
            'category'   => ['nullable', 'string', 'max:100'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active'  => ['nullable', 'boolean'],
        ]);

        $faq->update([
            'question'   => $validated['question'],
            'answer'     => $validated['answer'],
            'category'   => $validated['category'] ?? null,
            'sort_order' => $validated['sort_order'] ?? 0,
            'is_active'  => $request->boolean('is_active'),
        ]);

        return redirect()->route('app.admin.faqs.index')->with('status', 'FAQ updated successfully.');
    }

    public function destroy(Faq $faq): RedirectResponse
    {
        $faq->delete();

        return redirect()->route('app.admin.faqs.index')->with('status', 'FAQ deleted successfully.');
    }

    public function toggleStatus(Faq $faq): RedirectResponse
    {
        $faq->update(['is_active' => !$faq->is_active]);

        return redirect()->route('app.admin.faqs.index')->with('status', 'FAQ status updated.');
    }
}
