<?php

namespace App\Http\Controllers\Dashboard;

use App\Domain\Compliance\Register;
use App\Http\Controllers\Controller;
use Illuminate\View\View;

/**
 * The compliance checklist.
 *
 * Sir Peter asked for the state, the date and the law kept somewhere we can
 * look back at, and for the items to be ticked off as they are built rather
 * than tracked in a chat thread nobody can search a year from now.
 */
class AdminComplianceController extends Controller
{
    public function index(): View
    {
        return view('dashboard.admin.compliance.index', [
            'byJurisdiction' => Register::byJurisdiction(),
            'tally'          => Register::tally(),
            'problems'       => Register::problems(),
        ]);
    }
}
