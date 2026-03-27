<?php

namespace App\Http\Controllers;

use App\Models\Faq;
use App\Models\MembershipPlan;
use Illuminate\View\View;

class LandingPageController extends Controller
{
    public function __invoke(): View
    {
        $plans = MembershipPlan::query()
            ->active()
            ->ordered()
            ->with('features')
            ->get();

        $faqs = Faq::active()->ordered()->get();

        return view('landing', compact('plans', 'faqs'));
    }
}
