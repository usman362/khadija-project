@extends('layouts.dashboard')
@section('title', 'AI Chatbot Logs')
@section('content')

<div class="d-flex justify-content-between align-items-center flex-wrap mb-4">
    <div>
        <h4 class="mb-1"><i data-lucide="message-square" class="me-2" style="width:22px;height:22px;"></i> AI Chatbot Logs</h4>
        <p class="text-secondary mb-0">Review conversations users have had with the AI assistant.</p>
    </div>
    <a href="{{ route('app.admin.settings.chatbot') }}" class="btn btn-outline-primary">
        <i data-lucide="settings" style="width:16px;height:16px;"></i> Chatbot Settings
    </a>
</div>

{{-- Stats --}}
<div class="row g-3 mb-4">
    @foreach([
        ['label'=>'Conversations',   'value'=>number_format($stats['total_conversations']), 'color'=>'primary', 'icon'=>'message-circle'],
        ['label'=>'Messages',        'value'=>number_format($stats['total_messages']),      'color'=>'success', 'icon'=>'message-square'],
        ['label'=>'Total Tokens',    'value'=>number_format($stats['total_tokens']),        'color'=>'warning', 'icon'=>'coins'],
        ['label'=>'Active Today',    'value'=>number_format($stats['active_today']),        'color'=>'info',    'icon'=>'activity'],
    ] as $card)
    <div class="col-6 col-md-3">
        <div class="card border-{{ $card['color'] }}" style="background:rgba(0,0,0,0.02);">
            <div class="card-body">
                <div class="d-flex align-items-center gap-2">
                    <i data-lucide="{{ $card['icon'] }}" class="text-{{ $card['color'] }}" style="width:22px;height:22px;"></i>
                    <div>
                        <div class="h4 mb-0 text-{{ $card['color'] }}">{{ $card['value'] }}</div>
                        <div class="text-secondary small">{{ $card['label'] }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endforeach
</div>

{{-- Search --}}
<form method="GET" class="mb-3">
    <div class="input-group">
        <span class="input-group-text"><i data-lucide="search" style="width:16px;height:16px;"></i></span>
        <input type="text" name="search" class="form-control" placeholder="Search by conversation title, user name or email..." value="{{ request('search') }}">
        <button type="submit" class="btn btn-primary">Search</button>
        @if(request('search'))
            <a href="{{ route('app.admin.chatbot-logs.index') }}" class="btn btn-outline-secondary">Clear</a>
        @endif
    </div>
</form>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover mb-0 align-middle">
            <thead>
                <tr>
                    <th>User</th>
                    <th>Conversation</th>
                    <th>Messages</th>
                    <th>Tokens</th>
                    <th>Last Activity</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($conversations as $c)
                    <tr>
                        <td>
                            @if($c->user)
                                <div class="d-flex align-items-center">
                                    <img src="{{ $c->user->avatar_url }}" class="rounded-circle me-2" style="width:32px;height:32px;object-fit:cover;">
                                    <div>
                                        <div class="small"><strong>{{ $c->user->name }}</strong>
                                            @if($c->user->trashed())
                                                <span class="badge bg-secondary ms-1">deleted</span>
                                            @endif
                                        </div>
                                        <div class="small text-secondary">{{ $c->user->email }}</div>
                                    </div>
                                </div>
                            @else
                                <span class="text-secondary small">—</span>
                            @endif
                        </td>
                        <td class="small" style="max-width:280px;">{{ \Illuminate\Support\Str::limit($c->title, 60) }}</td>
                        <td class="small">{{ $c->messages_count }}</td>
                        <td class="small">{{ number_format($c->total_tokens) }}</td>
                        <td class="small">{{ $c->last_message_at?->diffForHumans() ?? '—' }}</td>
                        <td class="text-end">
                            <a href="{{ route('app.admin.chatbot-logs.show', $c) }}" class="btn btn-sm btn-outline-primary">
                                <i data-lucide="eye" style="width:14px;height:14px;"></i> View
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center py-5 text-secondary">
                            <i data-lucide="inbox" style="width:48px;height:48px;opacity:0.3;"></i>
                            <div class="mt-3">No conversations yet.</div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="mt-3">{{ $conversations->links() }}</div>

@endsection
