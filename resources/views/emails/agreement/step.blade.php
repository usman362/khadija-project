@extends('emails.layouts.base')

@section('title', 'Your agreement has moved')

@section('content')
{{-- The banner carries the meaning, because somebody skimming on a phone reads
     the coloured strip and the first line and nothing else. --}}
@php
    $banner = match ($step) {
        'changes_sent' => ['banner-warn', '✎', 'Changes are waiting for you to review'],
        'approved'     => ['banner-info', '✓', 'Terms approved'],
        'signed'       => ['banner-info', '✍', 'The agreement has been signed'],
        'declined'     => ['banner-warn', '⊘', 'The agreement was declined'],
        'funded'       => ['banner-info', '🔒', 'The deposit is secured'],
        default        => ['banner-info', 'ℹ', 'Your agreement has been updated'],
    };
@endphp
<div class="banner {{ $banner[0] }}"><span>{{ $banner[1] }}</span> {{ $banner[2] }}</div>

<h1>Hi {{ $user->name }},</h1>

<p>
    On <strong>{{ $data['title'] ?? 'your event' }}</strong>: {{ $data['message'] }}
</p>

@if(! empty($data['changed']))
    {{-- Named, not counted. "3 changes" tells a reader nothing they can act on,
         and the whole point of recording which fields moved was so this line
         could say them. --}}
    <p>What changed: <strong>{{ implode(', ', $data['changed']) }}</strong>.</p>
@endif

@if($step === 'changes_sent')
    <p>
        Because the terms moved, approvals and signatures given to the earlier version no longer
        stand. Both sides need to look again before anything is signed.
    </p>
@elseif($step === 'funded')
    <p>
        The price and the payment terms are now fixed. If something still needs to change, message
        the other side and agree it between you first.
    </p>
@endif

@include('emails.partials.button', ['url' => $data['url'], 'label' => 'Open the agreement'])
@endsection
