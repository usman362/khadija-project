<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Throwable;

/**
 * AI Contract Assistant (both). Generates a plain-English draft service
 * agreement from the event details supplied. Deterministic, template-based —
 * no external API. Always shown with a "not legal advice" disclaimer.
 */
class AiContractAssistantController extends Controller
{
    public function show(Request $request): View
    {
        $aiLayout = $request->user()?->activeRole() === 'supplier' ? 'layouts.professional' : 'layouts.client';

        return view('ai-tools.contract-assistant', [
            'aiLayout' => $aiLayout,
            'stats' => [
                ['Clause Sections', '6', ''], ['Deposit Default', '30%', ''],
                ['Cancellation Modes', '3', 'good'], ['Built-in', 'No API', 'good'],
            ],
        ]);
    }

    /**
     * Build a deterministic draft agreement from the supplied event details.
     */
    public function compute(Request $request): JsonResponse
    {
        try {
            $data = $request->validate([
                'service'       => ['required', 'string', 'max:200'],
                'client_name'   => ['nullable', 'string', 'max:120'],
                'provider_name' => ['nullable', 'string', 'max:120'],
                'total_price'   => ['required', 'numeric', 'min:0', 'max:99999999'],
                'event_date'    => ['required', 'date'],
                'deposit_pct'   => ['nullable', 'numeric', 'min:0', 'max:100'],
                'cancellation'  => ['nullable', 'in:flexible,standard,strict'],
            ]);

            $result = $this->buildAgreement($data);

            return response()->json(['success' => true, 'result' => $result]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => collect($e->errors())->flatten()->first() ?? 'Please check the form and try again.',
            ], 422);
        } catch (Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    /** @param array<string,mixed> $d */
    private function buildAgreement(array $d): array
    {
        $service  = trim((string) $d['service']);
        $client   = trim((string) ($d['client_name'] ?? '')) ?: 'the Client';
        $provider = trim((string) ($d['provider_name'] ?? '')) ?: 'the Provider';
        $total    = round((float) $d['total_price'], 2);
        $mode     = (string) ($d['cancellation'] ?? 'standard');

        $depositPct = $d['deposit_pct'] !== null && $d['deposit_pct'] !== ''
            ? (float) $d['deposit_pct']
            : 30.0;

        $deposit = round($total * $depositPct / 100, 2);
        $balance = round($total - $deposit, 2);

        $eventDate   = \Illuminate\Support\Carbon::parse($d['event_date']);
        $eventStr    = $eventDate->format('F j, Y');
        $depositDue  = 'upon signing this agreement';
        $balanceDate = $eventDate->copy()->subDays(7);
        $balanceStr  = $balanceDate->format('F j, Y');

        $money = fn (float $n) => '$' . number_format($n, 2);

        // --- Cancellation & Rescheduling text varies by mode ---
        $cutoff = match ($mode) {
            'flexible' => $eventDate->copy()->subDays(7),
            'strict'   => $eventDate->copy()->subDays(30),
            default    => $eventDate->copy()->subDays(14),
        };
        $cutoffStr = $cutoff->format('F j, Y');

        $cancellationBody = match ($mode) {
            'flexible' => "Either party may cancel this agreement in writing. If {$client} cancels on or before {$cutoffStr} (7 days before the event), the deposit of {$money($deposit)} is refundable less any non-recoverable costs already incurred by {$provider}. Cancellations after that date forfeit the deposit; any balance already paid beyond the deposit is refunded. If {$provider} cancels, all sums paid are returned in full.",
            'strict'   => "Cancellations must be made in writing. The deposit of {$money($deposit)} is non-refundable. If {$client} cancels after {$cutoffStr} (30 days before the event), the full contract amount of {$money($total)} remains due. If {$provider} cancels, all sums paid by {$client} are refunded in full and {$provider} will make reasonable efforts to suggest a comparable alternative.",
            default    => "Cancellations must be made in writing. If {$client} cancels on or before {$cutoffStr} (14 days before the event), any balance paid beyond the {$money($deposit)} deposit is refunded; the deposit itself is non-refundable. Cancellations after that date remain liable for the full amount of {$money($total)}. If {$provider} cancels, all sums paid by {$client} are refunded in full.",
        };

        $reschedBody = match ($mode) {
            'flexible' => "One reschedule to a mutually agreed date within 12 months is available at no extra charge, subject to {$provider}'s availability. Amounts already paid transfer to the new date.",
            'strict'   => "A reschedule may be requested no later than {$cutoffStr}, subject to {$provider}'s availability, and may incur a rebooking fee. Amounts already paid transfer to the new agreed date; the deposit remains non-refundable if a new date cannot be agreed.",
            default    => "One reschedule to a mutually agreed date within 6 months may be requested, subject to {$provider}'s availability. Amounts already paid transfer to the new date; a rebooking fee may apply if third-party costs change.",
        };

        $clauses = [
            [
                'heading' => '1. Scope of Services',
                'body'    => "{$provider} agrees to provide {$service} to {$client} in connection with the event scheduled for {$eventStr} (the \"Event\"). The services cover the arrangements discussed and agreed between the parties for the Event. Any work requested beyond this scope will be quoted and agreed separately in writing before it is carried out.",
            ],
            [
                'heading' => '2. Fees & Payment Schedule',
                'body'    => "The total fee for the services is {$money($total)}. A deposit of {$money($deposit)} ({$this->pctLabel($depositPct)}) is payable {$depositDue} to secure the date. The remaining balance of {$money($balance)} is due by {$balanceStr} (7 days before the Event). Payments may be made by card or bank transfer. The date is considered reserved once the deposit is received.",
            ],
            [
                'heading' => '3. Cancellation & Refunds',
                'body'    => $cancellationBody,
            ],
            [
                'heading' => '4. Rescheduling',
                'body'    => $reschedBody,
            ],
            [
                'heading' => '5. Liability & Force Majeure',
                'body'    => "{$provider}'s total liability under this agreement is limited to the total fee paid, being {$money($total)}. Neither party is liable for failure to perform caused by events beyond their reasonable control — including severe weather, illness, venue closure, government restrictions or other force majeure. In such cases the parties will act in good faith to reschedule or provide a fair, proportionate refund for services not delivered.",
            ],
            [
                'heading' => '6. Agreement & Signatures',
                'body'    => "This agreement represents the understanding between {$client} and {$provider} for the Event on {$eventStr}. By signing below, both parties confirm they have read and accept these terms.\n\nClient: {$client} — Signature: __________________  Date: ____________\n\nProvider: {$provider} — Signature: __________________  Date: ____________",
            ],
        ];

        $modeLabel = ucfirst($mode);

        return [
            'title'   => "Service Agreement — {$service}",
            'clauses' => $clauses,
            'disclaimer' => 'This is a draft template for convenience and is not legal advice — have a professional review before signing.',
            'summary' => "Draft agreement between {$client} and {$provider} for {$service} on {$eventStr}. Total {$money($total)} with a {$this->pctLabel($depositPct)} deposit of {$money($deposit)} and a {$money($balance)} balance due by {$balanceStr}. {$modeLabel} cancellation terms applied.",
        ];
    }

    private function pctLabel(float $pct): string
    {
        $rounded = round($pct, 2);
        return (floor($rounded) == $rounded ? (string) (int) $rounded : rtrim(rtrim(number_format($rounded, 2), '0'), '.')) . '%';
    }
}
