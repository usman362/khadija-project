@extends('emails.layouts.base')

@section('title', 'You have a new message')

@section('content')
<div class="banner banner-info"><span>✉</span> New message on GigResource</div>

<h1>Hi {{ $user->name }},</h1>

<p>
    <strong>{{ $data['sender'] }}</strong> sent you a message.
</p>

{{-- A preview, not the message. The thread, the attachments and the reply box
     are on the site, and an email that reproduces the conversation invites
     somebody to reply to the email instead, where nobody is listening. --}}
<p style="border-left:3px solid #e5e7eb;padding-left:12px;color:#4b5563;">
    {{ $data['preview'] }}
</p>

@include('emails.partials.button', ['url' => $data['url'], 'label' => 'Read and reply'])
@endsection
