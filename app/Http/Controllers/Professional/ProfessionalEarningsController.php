<?php

namespace App\Http\Controllers\Professional;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProfessionalEarningsController extends Controller
{
    public function index(Request $request): View
    {
        $stats = [
            'total_earnings' => 0,
            'this_month' => 0,
            'pending_payout' => 0,
        ];

        return view('professional.earnings.index', compact('stats'));
    }
}
