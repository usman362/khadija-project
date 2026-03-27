<?php

namespace App\Http\Controllers\Professional;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Event;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProfessionalGigController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();

        $query = Event::where('supplier_id', $user->id)
            ->with(['category:id,name,icon', 'supplier:id,name', 'client:id,name', 'bookings'])
            ->latest();

        // Search filter
        if ($request->filled('search')) {
            $query->where('title', 'like', '%' . $request->string('search') . '%');
        }

        // Status filter
        if ($request->filled('status')) {
            $query->where('status', $request->string('status')->toString());
        }

        // Category filter
        if ($request->filled('category')) {
            $query->where('category_id', $request->integer('category'));
        }

        $events = $query->paginate(12)->withQueryString();

        // Stats
        $stats = [
            'total' => Event::where('supplier_id', $user->id)->count(),
            'active' => Event::where('supplier_id', $user->id)->whereIn('status', ['pending', 'published', 'confirmed', 'in_progress'])->count(),
            'upcoming' => Event::where('supplier_id', $user->id)->where('starts_at', '>', now())->count(),
            'total_budget' => 0,  // placeholder
        ];

        // Calendar data: events for current month
        $month = $request->integer('month', (int) now()->format('m'));
        $year = $request->integer('year', (int) now()->format('Y'));
        $calendarEvents = Event::where('supplier_id', $user->id)
            ->whereNotNull('starts_at')
            ->whereMonth('starts_at', $month)
            ->whereYear('starts_at', $year)
            ->get(['id', 'title', 'starts_at', 'ends_at', 'status']);

        $categories = Category::active()->orderBy('sort_order')->orderBy('name')->get(['id', 'name']);

        return view('professional.gigs.index', compact('events', 'stats', 'calendarEvents', 'categories', 'month', 'year'));
    }

    public function show(Request $request, Event $event): View
    {
        $this->authorize('view', $event);

        $event->load([
            'category:id,name',
            'client:id,name,email',
            'supplier:id,name,email',
            'bookings.supplier:id,name',
            'bookings.client:id,name',
            'messages.sender:id,name',
        ]);

        return view('professional.gigs.show', compact('event'));
    }
}
