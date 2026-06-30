<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * AI Message Assistant (both). Writes clear, professional messages — replies,
 * follow-ups, quotes — in a chosen tone. Representative data.
 */
class AiMessageAssistantController extends Controller
{
    public function show(Request $request): View
    {
        $aiLayout = $request->user()?->hasRole('supplier') ? 'layouts.professional' : 'layouts.client';

        return view('ai-tools.message-assistant', [
            'aiLayout' => $aiLayout,
            'stats' => [
                ['Saved per Msg', '~3 min', 'good'], ['Tone Options', '4', ''],
                ['Languages', '12', ''], ['AI Confidence', '98%', 'good'],
            ],
            'intent' => 'Reply to Sarah Johnson — needs help with her July 15 event',
            'points' => "We're available\n\$1,850 package fits\nAsk about guest count",
            'tones' => ['Friendly & warm', 'Casual', 'Professional', 'Concise'],
            'suggested' => "Hi Sarah,\n\nThank you so much for reaching out — yes, I'm available for your July 15 event and would love to help bring it to life!\n\nFor an event like yours, my \$1,850 package is a perfect fit: it covers full ceremony and reception coverage. To tailor everything, could you let me know your approximate guest count?\n\nLooking forward to it!\nElite Events Co.",
        ]);
    }
}
