@php
    use App\Domain\Agreements\Workspace;
    use App\Domain\Requests\VenueRule;

    $signed = Workspace::clientSigned($fin) && Workspace::supplierSigned($fin);

    // Nothing here is left blank. A blank line in a document about money
    // reads as "nothing owed"; this reads as what it is.
    $unset = fn (?string $what = null) => '<i class="u">' . ($what ?? 'Not settled yet') . '</i>';
    $money = fn ($n) => $n !== null ? '$' . number_format((float) $n, 2) : null;
@endphp
<!doctype html>
<html>
<head>
<meta charset="utf-8">
<title>Agreement draft {{ $event?->reference() }}</title>
<style>
    @page { margin: 34px 38px 54px; }
    body { font-family: DejaVu Sans, sans-serif; font-size: 10.5px; color: #1f2937; line-height: 1.5; }
    .draft-mark { position: fixed; bottom: -36px; left: 0; right: 0; font-size: 8.5px; color: #9ca3af; }
    h1 { font-size: 17px; margin: 0 0 2px; color: #111827; }
    .ref { font-size: 10px; font-weight: bold; color: #6b7280; letter-spacing: .4px; }
    .banner { margin: 12px 0 16px; padding: 9px 11px; border: 1px solid #d97706;
              background: #fffbeb; color: #92400e; font-size: 10px; line-height: 1.45; }
    h2 { font-size: 11px; margin: 16px 0 5px; padding-bottom: 3px;
         border-bottom: 1px solid #e5e7eb; color: #111827; text-transform: uppercase; letter-spacing: .5px; }
    table { width: 100%; border-collapse: collapse; }
    td { padding: 4px 0; vertical-align: top; }
    td.k { width: 150px; color: #6b7280; }
    td.v { font-weight: bold; }
    .u { color: #b45309; font-style: italic; font-weight: normal; }
    .party { width: 50%; vertical-align: top; padding-right: 14px; }
    .note { color: #6b7280; font-size: 9.5px; margin: 3px 0 0; }
    .scope { white-space: pre-line; }
</style>
</head>
<body>

<div class="draft-mark">
    Draft &middot; {{ $event?->reference() }} &middot; printed {{ $generatedAt->format('M j, Y · g:i A') }}
    &middot; version {{ Workspace::version($fin) }}
</div>

<h1>{{ $signed ? 'Event Agreement' : 'Draft Event Agreement' }}</h1>
<div class="ref">{{ $event?->reference() }} &middot; {{ $event?->title ?? 'Event' }}</div>

{{-- What this is, before anything it says. --}}
<div class="banner">
    @if($signed)
        <b>Signed by both sides.</b> This copy is for your records. The contract of record is the one
        on your booking.
    @else
        <b>This is a draft, not a contract.</b> It records where the terms have reached on
        {{ $generatedAt->format('M j, Y') }} and binds nobody. Terms can still change, and either side
        can step away, until both have approved the same version and signed.
    @endif
</div>

<h2>The parties</h2>
<table>
    <tr>
        <td class="party">
            <b>{{ $client->name }}</b><br>
            Event host
            @if($client->public_id)<br>{{ \App\Support\GigResourceId::display($client->public_id) }}@endif
        </td>
        <td class="party">
            <b>{{ $pro?->name ?? 'Professional' }}</b><br>
            {{ $service ?? 'Professional' }}
            @if($pro?->public_id)<br>{{ \App\Support\GigResourceId::display($pro->public_id) }}@endif
        </td>
    </tr>
</table>

<h2>The event</h2>
<table>
    <tr><td class="k">Starts</td><td class="v">{{ $event?->starts_at?->format('M j, Y · g:i A') ?? 'Not set' }}</td></tr>
    <tr><td class="k">Ends</td><td class="v">{{ $event?->ends_at?->format('M j, Y · g:i A') ?? 'Not specified' }}</td></tr>
    <tr><td class="k">Location</td><td class="v">{{ VenueRule::place($event?->location) ?: trim(($event?->city ? $event->city . ', ' : '') . ($event?->state ?? '')) ?: 'Not set' }}</td></tr>
    <tr><td class="k">Guests</td><td class="v">{{ $event?->guest_count ?: 'Not set' }}</td></tr>
</table>

<h2>Service covered</h2>
<p class="note">Only what this professional bid on. Other services on this event have agreements of their own.</p>
<table>
    <tr><td class="k">Service</td><td class="v">{{ $service ?? 'This service' }}</td></tr>
    <tr><td class="k">Agreed price</td><td class="v">{!! $money($fin->agreed_price) ?? $unset('No price agreed yet') !!}</td></tr>
</table>

<h2>Scope and deliverables</h2>
@if($fin->scope)
    <div class="scope">{{ $fin->scope }}</div>
@else
    <p>{!! $unset('The scope has not been written down yet.') !!}</p>
@endif

<h2>When the service runs</h2>
<table>
    <tr><td class="k">Starts</td><td class="v">{!! $fin->service_start?->format('M j, Y · g:i A') ?? $unset() !!}</td></tr>
    <tr><td class="k">Ends</td><td class="v">{!! $fin->service_end?->format('M j, Y · g:i A') ?? $unset('Not specified') !!}</td></tr>
    <tr><td class="k">Availability</td><td class="v">{{ $fin->bid?->available_confirmed ? 'Confirmed for your date' : 'Not confirmed' }}</td></tr>
</table>

<h2>Deposit and payment</h2>
<table>
    <tr>
        <td class="k">Deposit</td>
        <td class="v">{!! $fin->deposit_percent
            ? $fin->deposit_percent . '% &middot; ' . $money($fin->deposit_amount)
            : $unset() !!}</td>
    </tr>
    <tr><td class="k">Balance due</td><td class="v">{!! $fin->balance_due_on?->format('M j, Y') ?? $unset() !!}</td></tr>
    <tr><td class="k">Payment terms</td><td class="v">{!! $fin->payment_terms ?: $unset() !!}</td></tr>
</table>

@if($fin->contract_body)
    <h2>Contract terms</h2>
    <div class="scope">{{ $fin->contract_body }}</div>
@endif

<h2>Where this has got to</h2>
<table>
    <tr><td class="k">Version</td><td class="v">{{ Workspace::version($fin) }}</td></tr>
    <tr>
        <td class="k">{{ $client->name }}</td>
        <td class="v">{{ Workspace::clientSigned($fin) ? 'Signed ' . $fin->client_signed_at?->format('M j, Y') : (Workspace::clientApproved($fin) ? 'Approved this version' : 'Has not approved this version') }}</td>
    </tr>
    <tr>
        <td class="k">{{ $pro?->name ?? 'Professional' }}</td>
        <td class="v">{{ Workspace::supplierSigned($fin) ? 'Signed ' . $fin->supplier_signed_at?->format('M j, Y') : (Workspace::supplierApproved($fin) ? 'Approved this version' : 'Has not approved this version') }}</td>
    </tr>
    @if($fin->change_request)
        <tr><td class="k">Changes asked for</td><td class="v">{{ $fin->change_request }}</td></tr>
    @endif
</table>

@unless($signed)
    <p class="note" style="margin-top:14px;">
        No signature appears on this page because none has been given on this version. A signed
        agreement is produced from your booking once both sides have signed.
    </p>
@endunless

</body>
</html>
