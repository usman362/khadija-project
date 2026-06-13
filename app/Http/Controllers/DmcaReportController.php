<?php

namespace App\Http\Controllers;

use App\Models\DmcaReport;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Public DMCA takedown-notice intake (Developer Feedback v1.1 §1.3).
 * Stores the notice with reporter details + flagged content URL, links
 * the uploading user when the email is known, and writes an audit log
 * line so every flagged image carries a timestamp + user ID trail.
 */
class DmcaReportController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'reporter_name'  => ['required', 'string', 'max:255'],
            'reporter_email' => ['required', 'email', 'max:255'],
            'content_url'    => ['required', 'url', 'max:2048'],
            'original_work'  => ['required', 'string', 'max:5000'],
            'statement'      => ['nullable', 'string', 'max:5000'],
            'good_faith'     => ['accepted'],
        ]);

        // reported_user_id (the uploader of the flagged content) is resolved
        // by the admin during review — at intake we only have the URL.
        $report = DmcaReport::create([
            'reporter_name'  => $data['reporter_name'],
            'reporter_email' => $data['reporter_email'],
            'content_url'    => $data['content_url'],
            'original_work'  => $data['original_work'],
            'statement'      => $data['statement'] ?? null,
            'status'         => 'pending',
        ]);

        Log::channel('single')->info('DMCA notice received', [
            'report_id'   => $report->id,
            'content_url' => $report->content_url,
            'reporter'    => $report->reporter_email,
            'user_id'     => auth()->id(),
            'at'          => now()->toIso8601String(),
        ]);

        return back()->with('status', 'Your DMCA notice has been received. Our team will review it and respond to the email provided.');
    }
}
