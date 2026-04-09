<?php

namespace App\Http\Controllers\Influencer;

use App\Domain\Influencer\Contracts\InfluencerServiceInterface;
use App\Domain\Influencer\DataTransferObjects\InfluencerApplicationData;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class JoinAsInfluencerController extends Controller
{
    public function __construct(private readonly InfluencerServiceInterface $service)
    {
    }

    public function show(): View
    {
        $tiers = config('influencer.tiers', []);
        return view('influencer.join', compact('tiers'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'full_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'social_media_links' => ['nullable', 'string', 'max:1000'],
            'audience_description' => ['nullable', 'string', 'max:2000'],
            'monthly_reach' => ['nullable', 'integer', 'min:0'],
        ]);

        // Social links can come as comma/newline separated string — convert to array
        $links = [];
        if (! empty($validated['social_media_links'])) {
            $parts = preg_split('/[\s,]+/', $validated['social_media_links']) ?: [];
            $links = array_values(array_filter(array_map('trim', $parts)));
        }

        $dto = InfluencerApplicationData::fromArray([
            'full_name' => $validated['full_name'],
            'email' => $validated['email'],
            'social_media_links' => $links,
            'audience_description' => $validated['audience_description'] ?? null,
            'monthly_reach' => $validated['monthly_reach'] ?? 0,
        ]);

        $this->service->apply($dto);

        return redirect()
            ->route('influencer.join')
            ->with('status', 'Application submitted! Our team will review it and get back to you soon.');
    }
}
