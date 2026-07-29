@extends('layouts.dashboard')

@section('title', 'Website Content')

@section('content')
    <div class="d-flex justify-content-between align-items-center flex-wrap mb-4">
        <div>
            <h4 class="mb-1"><i data-lucide="layout-template" class="me-2" style="width:24px;height:24px;"></i> Website Content</h4>
            <p class="text-secondary mb-0">Edit the words and pictures on your homepage. The design and layout stay as they are.</p>
        </div>
        <a href="{{ url('/') }}" target="_blank" rel="noopener" class="btn btn-outline-primary btn-icon-text">
            <i class="btn-icon-prepend" data-lucide="external-link"></i> View homepage
        </a>
    </div>

    <div class="row g-3">
        @foreach($schema as $key => $def)
            @php $section = $sections->get($key); @endphp
            <div class="col-md-6 col-xl-4">
                <div class="card h-100">
                    <div class="card-body d-flex flex-column">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <h6 class="mb-0">{{ $def['name'] }}</h6>
                            @if($section && ! $section->is_active)
                                <span class="badge bg-secondary">Hidden</span>
                            @endif
                        </div>

                        @if(! empty($def['note']))
                            <p class="text-secondary small mb-3">{{ $def['note'] }}</p>
                        @elseif($section?->heading)
                            <p class="text-secondary small mb-3">“{{ \Illuminate\Support\Str::limit(str_replace('|', ' ', $section->heading), 70) }}”</p>
                        @else
                            <p class="text-secondary small mb-3">&nbsp;</p>
                        @endif

                        <div class="mt-auto d-flex align-items-center gap-2">
                            <a href="{{ route('app.admin.content.edit', $key) }}" class="btn btn-sm btn-primary btn-icon-text">
                                <i class="btn-icon-prepend" data-lucide="pencil"></i> Edit
                            </a>
                            @php
                                // sum() hands the callback only the value, so the
                                // repeater name has to come off the keys.
                                $count = collect(array_keys($def['repeaters'] ?? []))
                                    ->sum(fn ($n) => count($section?->item($n, []) ?? []));
                            @endphp
                            @if($count)
                                <span class="text-secondary small">{{ $count }} items</span>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
@endsection
