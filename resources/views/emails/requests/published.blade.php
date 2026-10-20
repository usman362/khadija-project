@extends('emails.layouts.base')

@section('title', 'Your request is live')

@section('content')
<div class="banner banner-info"><span>📣</span> Your request is live</div>

<h1>Hi {{ $user->name }},</h1>

<p>
    <strong>{{ $data['title'] ?? 'Your request' }}</strong> has gone out to professionals in your state.
    They can see it now and send you proposals.
</p>

@if(! empty($data['fee']))
    {{-- The receipt half. Since the posting fee moved to publish there is a
         charge to account for at exactly this moment, and this notification is
         the only record of it the client can come back to. --}}
    <p>
        Posting fee: <strong>${{ number_format((float) $data['fee'], 2) }}</strong>.
        Nothing further is charged unless you go on to finalize an agreement.
    </p>
@endif

<p>Proposals land on your Proposals page. Nothing is booked until you pick somebody.</p>

@include('emails.partials.button', ['url' => $data['url'], 'label' => 'View your request'])
@endsection
