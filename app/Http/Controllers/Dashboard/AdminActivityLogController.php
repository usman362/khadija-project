<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminActivityLogController extends Controller
{
    public function index(Request $request): View
    {
        $query = ActivityLog::query()
            ->with('user:id,name,email,deleted_at')
            ->latest('created_at');

        // Filter: action
        if ($request->filled('action')) {
            $query->where('action', $request->input('action'));
        }

        // Filter: user (search by name or email)
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('subject_identifier', 'like', "%{$search}%")
                  ->orWhereHas('user', function ($inner) use ($search) {
                      $inner->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                  });
            });
        }

        // Filter: IP address
        if ($request->filled('ip')) {
            $query->where('ip_address', 'like', '%' . $request->input('ip') . '%');
        }

        // Filter: date range
        if ($request->filled('from')) {
            $query->whereDate('created_at', '>=', $request->input('from'));
        }
        if ($request->filled('to')) {
            $query->whereDate('created_at', '<=', $request->input('to'));
        }

        $logs = $query->paginate(30)->withQueryString();

        // Stats for the filter bar
        $stats = [
            'total'            => ActivityLog::count(),
            'login'            => ActivityLog::where('action', 'login')->count(),
            'logout'           => ActivityLog::where('action', 'logout')->count(),
            'login_failed'     => ActivityLog::where('action', 'login_failed')->count(),
            'password_changed' => ActivityLog::where('action', 'password_changed')->count(),
            'password_reset'   => ActivityLog::where('action', 'password_reset')->count(),
        ];

        return view('dashboard.admin.activity-logs.index', compact('logs', 'stats'));
    }
}
