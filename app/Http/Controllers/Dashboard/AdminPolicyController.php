<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\PolicyPage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminPolicyController extends Controller
{
    public function index(): View
    {
        $policies = PolicyPage::orderBy('id')->get();

        // Ensure all 3 default policies exist
        $defaults = [
            ['slug' => 'privacy-policy',       'title' => 'Privacy Policy'],
            ['slug' => 'payment-policy',       'title' => 'Payment Policy'],
            ['slug' => 'cancellation-policy',  'title' => 'Cancellation & Refund Policy'],
        ];

        foreach ($defaults as $default) {
            if (!$policies->where('slug', $default['slug'])->count()) {
                PolicyPage::create([
                    'slug'      => $default['slug'],
                    'title'     => $default['title'],
                    'content'   => '<p>Content coming soon.</p>',
                    'is_active' => true,
                ]);
            }
        }

        if ($policies->isEmpty()) {
            $policies = PolicyPage::orderBy('id')->get();
        }

        return view('dashboard.admin.policies.index', compact('policies'));
    }

    public function edit(PolicyPage $policy): View
    {
        return view('dashboard.admin.policies.edit', compact('policy'));
    }

    public function update(Request $request, PolicyPage $policy): RedirectResponse
    {
        $validated = $request->validate([
            'title'     => ['required', 'string', 'max:255'],
            'content'   => ['required', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $policy->update([
            'title'     => $validated['title'],
            'content'   => $validated['content'],
            'is_active' => $request->boolean('is_active'),
        ]);

        return redirect()->route('app.admin.policies.index')->with('status', 'Policy page updated successfully.');
    }
}
