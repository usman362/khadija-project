@extends('layouts.dashboard')
@section('title', 'Conversation Detail')
@section('content')

<div class="d-flex align-items-center mb-4">
    <a href="{{ route('app.admin.chatbot-logs.index') }}" class="btn btn-sm btn-outline-secondary me-3">
        <i data-lucide="arrow-left" style="width:14px;height:14px;"></i> Back to Logs
    </a>
    <div>
        <h4 class="mb-1">{{ $conversation->title }}</h4>
        <div class="small text-secondary">
            @if($conversation->user)
                <strong>{{ $conversation->user->name }}</strong> ({{ $conversation->user->email }})
            @endif
            &middot; {{ $conversation->messages->count() }} messages &middot; {{ number_format($conversation->total_tokens) }} tokens
            &middot; Last activity: {{ $conversation->last_message_at?->format('M j, Y g:i A') }}
        </div>
    </div>
</div>

<div class="card">
    <div class="card-body" style="max-width: 900px; margin: 0 auto;">
        @forelse($conversation->messages as $msg)
            @if($msg->role === 'user')
                <div class="d-flex justify-content-end mb-3">
                    <div style="max-width:75%; background:linear-gradient(135deg,#6366f1,#8b5cf6); color:#fff; padding:12px 16px; border-radius:16px; border-bottom-right-radius:4px;">
                        <div style="font-size:14px; white-space:pre-wrap; word-wrap:break-word;">{{ $msg->content }}</div>
                        <div style="font-size:10px; opacity:0.75; margin-top:6px;">{{ $msg->created_at->format('g:i A') }}</div>
                    </div>
                </div>
            @elseif($msg->role === 'assistant')
                <div class="d-flex justify-content-start mb-3">
                    <div style="max-width:75%; background:rgba(0,0,0,0.04); padding:12px 16px; border-radius:16px; border-bottom-left-radius:4px;">
                        <div style="font-size:11px; font-weight:600; color:#6366f1; margin-bottom:4px;">AI Assistant</div>
                        <div style="font-size:14px; white-space:pre-wrap; word-wrap:break-word;">{{ $msg->content }}</div>
                        <div style="font-size:10px; color:#94a3b8; margin-top:6px;">
                            {{ $msg->created_at->format('g:i A') }}
                            @if($msg->tokens_used > 0) &middot; {{ number_format($msg->tokens_used) }} tokens @endif
                        </div>
                    </div>
                </div>
            @endif
        @empty
            <div class="text-center text-secondary py-5">No messages in this conversation.</div>
        @endforelse
    </div>
</div>

@endsection
