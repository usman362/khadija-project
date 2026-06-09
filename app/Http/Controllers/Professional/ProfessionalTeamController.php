<?php

namespace App\Http\Controllers\Professional;

use App\Http\Controllers\Controller;
use App\Models\Shift;
use App\Models\StaffMember;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Team & Staffing — a professional's crew + shifts. Full backend:
 * StaffMember + Shift models, CRUD, on-shift / open-shift tracking and a
 * real labor-cost estimate (shift hours × staff hourly rate).
 *
 * Route base: /professional/team
 */
class ProfessionalTeamController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();

        $staff = StaffMember::where('supplier_id', $user->id)
            ->where('status', 'active')->orderBy('name')->get();
        $totalStaff = $staff->count();

        $onShift = Shift::where('supplier_id', $user->id)
            ->where('status', 'on_shift')
            ->with('staff')
            ->orderBy('starts_at')
            ->get();

        $openShifts = Shift::where('supplier_id', $user->id)
            ->where('status', 'open')
            ->orderBy('starts_at')
            ->get();

        // Labor cost for this week (staffed shifts).
        $weekStart = now()->startOfWeek();
        $weekEnd   = now()->endOfWeek();
        $weekShifts = Shift::where('supplier_id', $user->id)
            ->whereNotNull('staff_id')
            ->whereIn('status', ['assigned', 'on_shift', 'completed'])
            ->whereBetween('starts_at', [$weekStart, $weekEnd])
            ->with('staff')
            ->get();

        $laborHours   = round($weekShifts->sum(fn ($s) => $s->hours()), 1);
        $laborCost    = round($weekShifts->sum(fn ($s) => $s->hours() * (float) ($s->staff?->hourly_rate ?? 0)), 2);
        $laborWorkers = $weekShifts->pluck('staff_id')->unique()->count();

        $stats = [
            'total_staff' => $totalStaff,
            'on_shift'    => $onShift->count(),
            'open_shifts' => $openShifts->count(),
        ];

        $labor = [
            'cost'    => $laborCost,
            'hours'   => $laborHours,
            'workers' => $laborWorkers,
        ];

        return view('professional.team.index', compact('staff', 'onShift', 'openShifts', 'stats', 'labor'));
    }

    /** Add a crew member. */
    public function storeStaff(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name'        => ['required', 'string', 'max:120'],
            'role'        => ['nullable', 'string', 'max:80'],
            'phone'       => ['nullable', 'string', 'max:40'],
            'email'       => ['nullable', 'email', 'max:160'],
            'hourly_rate' => ['nullable', 'numeric', 'min:0', 'max:9999'],
        ]);

        StaffMember::create([
            'supplier_id' => $request->user()->id,
            'status'      => 'active',
        ] + $data);

        return back()->with('status', 'Team member added.');
    }

    /** Create a new (open) shift. */
    public function storeShift(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'role'      => ['required', 'string', 'max:80'],
            'location'  => ['nullable', 'string', 'max:120'],
            'starts_at' => ['required', 'date'],
            'ends_at'   => ['required', 'date', 'after:starts_at'],
            'slots'     => ['nullable', 'integer', 'min:1', 'max:50'],
            'staff_id'  => ['nullable', 'integer', 'exists:staff_members,id'],
        ]);

        $staffId = $data['staff_id'] ?? null;

        Shift::create([
            'supplier_id' => $request->user()->id,
            'staff_id'    => $staffId,
            'role'        => $data['role'],
            'location'    => $data['location'] ?? null,
            'starts_at'   => $data['starts_at'],
            'ends_at'     => $data['ends_at'],
            'slots'       => $data['slots'] ?? 1,
            'status'      => $staffId ? 'on_shift' : 'open',
        ]);

        return back()->with('status', 'Shift created.');
    }

    /** Fill an open shift by assigning an available crew member. */
    public function fillShift(Request $request, Shift $shift): RedirectResponse
    {
        $user = $request->user();
        abort_unless($shift->supplier_id === $user->id, 403);

        if ($shift->status !== 'open') {
            return back()->with('status', 'That shift is already filled.');
        }

        // Optional explicit staff pick, else first active member free at that time.
        $staffId = $request->integer('staff_id') ?: null;

        if ($staffId) {
            $staff = StaffMember::where('supplier_id', $user->id)->where('id', $staffId)->first();
        } else {
            $busyIds = Shift::where('supplier_id', $user->id)
                ->where('status', 'on_shift')
                ->pluck('staff_id')->filter()->all();
            $staff = StaffMember::where('supplier_id', $user->id)
                ->where('status', 'active')
                ->whereNotIn('id', $busyIds)
                ->orderBy('name')
                ->first();
        }

        if (! $staff) {
            return back()->with('status', 'No available crew member to fill this shift — add more staff first.');
        }

        $shift->update(['staff_id' => $staff->id, 'status' => 'on_shift']);

        return back()->with('status', "Shift filled by {$staff->name}.");
    }
}
