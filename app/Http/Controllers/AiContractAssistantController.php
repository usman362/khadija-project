<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * AI Contract Assistant (both). Plain-English breakdown of an agreement — key
 * terms, important dates and anything worth a second look. Representative data.
 * Always shown with a "not legal advice" disclaimer.
 */
class AiContractAssistantController extends Controller
{
    public function show(Request $request): View
    {
        $aiLayout = $request->user()?->hasRole('supplier') ? 'layouts.professional' : 'layouts.client';

        return view('ai-tools.contract-assistant', [
            'aiLayout' => $aiLayout,
            'stats' => [
                ['Clauses Reviewed', '14', ''], ['Points to Check', '2', 'warn'],
                ['Readability', 'Clear', 'good'], ['AI Confidence', '96%', 'good'],
            ],
            'document' => 'Service Agreement — Johnson Wedding.pdf',
            'summary' => [
                ['Services & scope', 'good', 'The professional vendor provides floral & décor services for the event described — clearly defined.'],
                ['Worth checking — Deposit', 'warn', 'A 30% deposit ($2,250) is non-refundable. Make sure you’re comfortable with this.'],
                ['Worth checking — Cancellation', 'warn', 'Cancellation allowed up to 14 days before the event. After that, the full amount is due.'],
                ['Payment terms', 'good', 'Balance due 7 days before the event, by card or bank transfer.'],
            ],
        ]);
    }
}
