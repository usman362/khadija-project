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
        $view = $request->string('view')->toString() ?: 'my-gigs';

        // ── My Gigs (assigned to this supplier) ──
        $myGigsQuery = Event::where('supplier_id', $user->id)
            ->with(['categories:id,name,icon', 'supplier:id,name', 'client:id,name', 'bookings'])
            ->latest();

        if ($request->filled('search') && $view === 'my-gigs') {
            $myGigsQuery->where('title', 'like', '%' . $request->string('search') . '%');
        }
        if ($request->filled('status') && $view === 'my-gigs') {
            $myGigsQuery->where('status', $request->string('status')->toString());
        }
        if ($request->filled('category') && $view === 'my-gigs') {
            $catId = $request->integer('category');
            $myGigsQuery->where(function ($q) use ($catId) {
                $q->where('category_id', $catId)
                  ->orWhereHas('categories', fn($q2) => $q2->where('categories.id', $catId));
            });
        }

        $myGigs = $myGigsQuery->paginate(12, ['*'], 'my_page')->withQueryString();

        // My Gigs Stats
        $stats = [
            'total' => Event::where('supplier_id', $user->id)->count(),
            'active' => Event::where('supplier_id', $user->id)->whereIn('status', ['pending', 'published', 'confirmed', 'in_progress'])->count(),
            'upcoming' => Event::where('supplier_id', $user->id)->where('starts_at', '>', now())->count(),
            'completed' => Event::where('supplier_id', $user->id)->where('status', 'completed')->count(),
        ];

        // ── Browse Available Events (published by clients, not yet assigned to this supplier) ──
        $browseQuery = Event::where('status', 'published')
            ->where(function ($q) use ($user) {
                $q->whereNull('supplier_id')
                  ->orWhere('supplier_id', '!=', $user->id);
            })
            ->with(['categories:id,name,icon', 'client:id,name'])
            ->latest();

        if ($request->filled('search') && $view === 'browse') {
            $browseQuery->where('title', 'like', '%' . $request->string('search') . '%');
        }
        if ($request->filled('category') && $view === 'browse') {
            $catId = $request->integer('category');
            $browseQuery->where(function ($q) use ($catId) {
                $q->where('category_id', $catId)
                  ->orWhereHas('categories', fn($q2) => $q2->where('categories.id', $catId));
            });
        }

        $browseEvents = $browseQuery->paginate(12, ['*'], 'browse_page')->withQueryString();

        $browseStats = [
            'total' => Event::where('status', 'published')->where(function ($q) use ($user) {
                $q->whereNull('supplier_id')->orWhere('supplier_id', '!=', $user->id);
            })->count(),
        ];

        // Calendar data: events for current month (my gigs)
        $month = $request->integer('month', (int) now()->format('m'));
        $year = $request->integer('year', (int) now()->format('Y'));
        $calendarEvents = Event::where('supplier_id', $user->id)
            ->whereNotNull('starts_at')
            ->whereMonth('starts_at', $month)
            ->whereYear('starts_at', $year)
            ->get(['id', 'title', 'starts_at', 'ends_at', 'status']);

        $categories = Category::active()->orderBy('sort_order')->orderBy('name')->get(['id', 'name']);

        return view('professional.gigs.index', compact(
            'myGigs', 'stats', 'browseEvents', 'browseStats',
            'calendarEvents', 'categories', 'month', 'year', 'view'
        ));
    }

    public function show(Request $request, Event $event): View
    {
        $event->load([
            'categories:id,name',
            'client:id,name,email',
            'supplier:id,name,email',
            'bookings.supplier:id,name',
            'bookings.client:id,name',
            'messages.sender:id,name',
        ]);

        return view('professional.gigs.show', compact('event'));
    }
}
