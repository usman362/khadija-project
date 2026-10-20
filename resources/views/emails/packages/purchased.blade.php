@extends('emails.layouts.base')

@section('title', 'Your package request')

@section('content')
<div class="banner banner-info"><span>📦</span> Package requested</div>

<h1>Hi {{ $user->name }},</h1>

<p>
    You requested <strong>{{ $data['title'] }}</strong>
    @if($data['supplier']) from <strong>{{ $data['supplier'] }}</strong>@endif.
</p>

<p>
    Package price: <strong>${{ number_format((float) $data['price'], 2) }}</strong>.
    @if(! empty($data['fee']))
        Agreement fee: <strong>${{ number_format((float) $data['fee'], 2) }}</strong>.
    @endif
</p>

{{-- "Requested", not "booked". A package purchase opens a booking the
     professional still has to accept, and telling the client it is confirmed
     would be the platform promising something nobody agreed to yet. --}}
<p>It is not confirmed until they accept it. We will let you know as soon as they do.</p>

@include('emails.partials.button', ['url' => $data['url'], 'label' => 'View your request'])
@endsection
