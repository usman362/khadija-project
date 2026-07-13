@extends('layouts.dashboard')
@section('title', 'Productivity Tools')
@section('content')
<div class="d-flex justify-content-between align-items-center flex-wrap mb-4">
    <div>
        <h4 class="mb-1"><i data-lucide="sparkles" class="me-2" style="width:24px;height:24px;"></i> Productivity Tools</h4>
        <p class="text-secondary mb-0">Enable or disable productivity tools across client &amp; professional sidebars and the /ai-tools hub.</p>
    </div>
    <div class="text-end">
        <span class="badge bg-success">{{ $counts['enabled'] }} enabled</span>
        <span class="badge bg-secondary">{{ $counts['disabled'] }} disabled</span>
        <span class="badge bg-info">{{ $counts['total'] }} total</span>
    </div>
</div>

@if(session('status'))
    <div class="alert alert-success alert-dismissible fade show">{{ session('status') }}<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>
@endif

@php
    $audienceMeta = [
        'client'       => ['label' => 'Client',       'badge' => 'primary'],
        'professional' => ['label' => 'Professional', 'badge' => 'warning'],
        'both'         => ['label' => 'Both',          'badge' => 'info'],
    ];
@endphp

@foreach($tools as $audience => $group)
    <div class="card mb-4">
        <div class="card-header d-flex align-items-center">
            <span class="badge bg-{{ $audienceMeta[$audience]['badge'] ?? 'secondary' }} me-2">{{ $audienceMeta[$audience]['label'] ?? ucfirst($audience) }}</span>
            <span class="text-secondary">{{ $group->count() }} tools</span>
        </div>
        <div class="card-body p-0">
            <table class="table table-hover mb-0 align-middle">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Purpose</th>
                        <th>Status</th>
                        <th class="text-end">Enabled</th>
                    </tr>
                </thead>
                <tbody>
                @foreach($group as $tool)
                    <tr>
                        <td class="fw-semibold">{{ $tool['name'] }}</td>
                        <td class="text-secondary small">{{ $tool['purpose'] }}</td>
                        <td>
                            <span class="badge bg-{{ $tool['status'] === 'live' ? 'success' : 'secondary' }}">{{ ucfirst($tool['status']) }}</span>
                        </td>
                        <td class="text-end">
                            <form method="POST" action="{{ route('app.ai-tools-admin.update', $tool['key']) }}" class="d-inline">
                                @csrf
                                <input type="hidden" name="enabled" value="0">
                                <div class="form-check form-switch d-inline-block">
                                    <input class="form-check-input" type="checkbox" role="switch"
                                           name="enabled" value="1"
                                           id="tool-{{ $tool['key'] }}"
                                           onchange="this.form.submit()"
                                           {{ $tool['enabled'] ? 'checked' : '' }}>
                                    <label class="form-check-label visually-hidden" for="tool-{{ $tool['key'] }}">Toggle {{ $tool['name'] }}</label>
                                </div>
                            </form>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endforeach
@endsection
