<?php

namespace App\Http\Controllers\Professional;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProfessionalReviewController extends Controller
{
    public function index(Request $request): View
    {
        $stats = [
            'total_reviews' => 0,
            'avg_rating' => 0,
            'five_star' => 0,
            'four_star' => 0,
        ];

        return view('professional.reviews.index', compact('stats'));
    }
}
