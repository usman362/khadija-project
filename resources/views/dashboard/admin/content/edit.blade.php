@extends('layouts.dashboard')

@section('title', $definition['name'])

@section('content')
    @php
        /** Renders one declared field. Kept here so the markup for a text box
            exists once rather than per section. */
        $field = function (string $name, array $spec, $value) {
            $id = 'f_' . str_replace(['[', ']', '.'], '_', $name);
            $html = '<label class="form-label" for="' . $id . '">' . e($spec['label']) . '</label>';
            if ($spec['type'] === 'textarea') {
                $html .= '<textarea class="form-control" id="' . $id . '" name="' . $name . '" rows="' . ($spec['rows'] ?? 3) . '">' . e($value) . '</textarea>';
            } elseif ($spec['type'] === 'url') {
                $html .= '<input type="url" class="form-control" id="' . $id . '" name="' . $name . '" value="' . e($value) . '" placeholder="https://…">';
            } else {
                $html .= '<input type="text" class="form-control" id="' . $id . '" name="' . $name . '" value="' . e($value) . '">';
            }
            if (! empty($spec['hint'])) {
                $html .= '<div class="form-text">' . e($spec['hint']) . '</div>';
            }
            return $html;
        };
    @endphp

    <div class="d-flex justify-content-between align-items-center flex-wrap mb-4">
        <div>
            <h4 class="mb-1">{{ $definition['name'] }}</h4>
            <p class="text-secondary mb-0">{{ $definition['note'] ?? 'Homepage section' }}</p>
        </div>
        <a href="{{ route('app.admin.content.index') }}" class="btn btn-outline-secondary btn-icon-text">
            <i class="btn-icon-prepend" data-lucide="arrow-left"></i> All sections
        </a>
    </div>

    @if(session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        </div>
    @endif

    <form method="POST" action="{{ route('app.admin.content.update', $key) }}" enctype="multipart/form-data">
        @csrf

        <div class="card mb-3">
            <div class="card-body">
                @foreach($definition['fields'] ?? [] as $name => $spec)
                    @if($spec['type'] === 'image')
                        <div class="mb-4">
                            <label class="form-label">{{ $spec['label'] }}</label>
                            @if($section->imageUrl())
                                <div class="mb-2">
                                    <img src="{{ $section->imageUrl() }}" alt="" style="max-width:260px;max-height:150px;object-fit:cover;border-radius:8px;border:1px solid rgba(0,0,0,.1);">
                                </div>
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="checkbox" name="remove_image" value="1" id="remove_image">
                                    <label class="form-check-label" for="remove_image">Remove this picture</label>
                                </div>
                            @endif
                            <input type="file" class="form-control" name="image" accept="image/*">
                            @if(! empty($spec['hint']))<div class="form-text">{{ $spec['hint'] }}</div>@endif
                        </div>
                    @else
                        <div class="mb-4">
                            {!! $field($name, $spec, old($name, $section->{$name === 'body' ? 'body' : $name})) !!}
                        </div>
                    @endif
                @endforeach

                @foreach($definition['extra'] ?? [] as $name => $spec)
                    <div class="mb-4">
                        {!! $field("payload[$name]", $spec, old("payload.$name", $section->item($name))) !!}
                    </div>
                @endforeach

                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" role="switch" name="is_active" value="1" id="is_active" @checked(old('is_active', $section->is_active))>
                    <label class="form-check-label" for="is_active">Show this section on the homepage</label>
                </div>
            </div>
        </div>

        @foreach($definition['repeaters'] ?? [] as $repeater => $spec)
            @php
                $rows = old("payload.$repeater", $section->item($repeater, []));
                // Always render exactly as many rows as the design has slots, so
                // an admin can fill a blank one but cannot add a sixth column.
                $rows = array_pad(array_slice($rows, 0, $spec['max']), $spec['min'], []);
            @endphp
            <div class="card mb-3">
                <div class="card-body">
                    <h6 class="mb-1">{{ $spec['label'] }}</h6>
                    <p class="text-secondary small">{{ $spec['min'] === $spec['max'] ? 'The design has ' . $spec['min'] . ' of these — you can change the wording, not how many.' : '' }}</p>

                    <div class="row g-3">
                        @foreach($rows as $i => $row)
                            <div class="col-lg-6">
                                <div class="border rounded p-3 h-100">
                                    <div class="text-secondary small fw-bold mb-2">#{{ $i + 1 }}</div>
                                    @foreach($spec['fields'] as $f => $fs)
                                        @if($fs['type'] === 'image')
                                            <div class="mb-3">
                                                <label class="form-label">{{ $fs['label'] }}</label>
                                                @php
                                                    $img = $row[$f] ?? null;
                                                    $src = $img ? (str_starts_with($img, 'http') ? $img : \Illuminate\Support\Facades\Storage::url($img)) : null;
                                                @endphp
                                                @if($src)
                                                    <div class="mb-2"><img src="{{ $src }}" alt="" style="max-width:150px;max-height:90px;object-fit:cover;border-radius:6px;border:1px solid rgba(0,0,0,.1);"></div>
                                                @endif
                                                <input type="file" class="form-control form-control-sm" name="images[{{ $repeater }}][{{ $i }}]" accept="image/*">
                                                {{-- Carried through so leaving the file input empty keeps
                                                     the current picture instead of clearing it. --}}
                                                <input type="hidden" name="payload[{{ $repeater }}][{{ $i }}][{{ $f }}]" value="{{ $img }}">
                                            </div>
                                        @else
                                            <div class="mb-3">
                                                {!! $field("payload[$repeater][$i][$f]", $fs, $row[$f] ?? '') !!}
                                            </div>
                                        @endif
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        @endforeach

        <div class="d-flex gap-2">
            <button type="submit" class="btn btn-primary btn-icon-text">
                <i class="btn-icon-prepend" data-lucide="save"></i> Save changes
            </button>
            <a href="{{ url('/') }}" target="_blank" rel="noopener" class="btn btn-outline-secondary btn-icon-text">
                <i class="btn-icon-prepend" data-lucide="external-link"></i> View homepage
            </a>
        </div>
    </form>
@endsection
