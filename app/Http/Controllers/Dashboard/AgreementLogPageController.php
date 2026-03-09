<?php

namespace App\Http\Controllers\Dashboard;

use App\Domain\Auth\Enums\RoleName;
use App\Http\Controllers\Controller;
use App\Models\AgreementLog;
use App\Models\Booking;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AgreementLogPageController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();

        $query = AgreementLog::query()
            ->where('subject_type', 'booking')
            ->with('changer:id,name,email')
            ->latest('created_at');

        if (! $user->isAdmin()) {
            $bookingIds = Booking::query()
                ->where(function ($q) use ($user): void {
                    $q->where('client_id', $user->id)
                        ->orWhere('supplier_id', $user->id);
                })
                ->pluck('id');
            $query->whereIn('subject_id', $bookingIds);
        }

        $logs = $query->paginate(20);

        $bookingIds = $logs->pluck('subject_id')->unique()->filter()->values();
        $bookings = Booking::with('event:id,title')->whereIn('id', $bookingIds)->get()->keyBy('id');

        return view('dashboard.agreement-log.index', [
            'logs' => $logs,
            'bookings' => $bookings,
        ]);
    }
}
