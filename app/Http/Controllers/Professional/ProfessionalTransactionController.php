<?php

namespace App\Http\Controllers\Professional;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProfessionalTransactionController extends Controller
{
    public function index(Request $request): View
    {
        $stats = [
            'total_transactions' => 0,
            'total_amount' => 0,
            'this_month' => 0,
        ];

        return view('professional.transactions.index', compact('stats'));
    }
}
