@extends($layout)

@section('title', $definition['title'])

@push('styles')
    @include('disputes._styles')
@endpush

@section('content')
<div class="dsp-head">
    <div>
        <h1 class="dsp-h1">{{ $definition['title'] }}</h1>
        <p class="dsp-sub">{{ $definition['purpose'] }}</p>
    </div>
    <a href="{{ route('forms.index') }}" class="cl-btn">Back</a>
</div>

<form method="POST" action="{{ route('forms.store', $key) }}">
    @csrf

    <div class="dsp-card">
        @foreach($definition['fields'] as $field)
            @php $name = $field['name'] ?? null; @endphp

            @if(($field['type'] ?? '') === 'certification')
                {{-- Never pre-ticked, and the wording shown here is the
                     wording stored with the submission. A certification that
                     only points at a config file is a signature on a document
                     someone can edit afterwards. --}}
                <label class="dsp-cert" style="margin-top:6px;">
                    <input type="checkbox" name="{{ $name }}" value="1" required>
                    <span>{{ $field['text'] }}</span>
                </label>
                @error($name) <p class="dsp-err">{{ $message }}</p> @enderror
                @continue
            @endif

            <div class="dsp-field">
                <label class="dsp-label" for="{{ $name }}">
                    {{ $field['label'] }}
                    @unless($field['required'] ?? false)
                        <span style="font-weight:600;color:var(--text-muted);">(optional)</span>
                    @endunless
                </label>

                @switch($field['type'] ?? 'text')
                    @case('booking')
                        <select name="{{ $name }}" id="{{ $name }}" class="dsp-select" @required($field['required'] ?? false)>
                            <option value="">Choose a booking…</option>
                            @foreach($bookings as $booking)
                                @php $other = $booking->client_id === auth()->id() ? $booking->supplier : $booking->client; @endphp
                                <option value="{{ $booking->id }}" @selected(old($name) == $booking->id)>
                                    {{ $booking->event?->title ?? 'Booking #' . $booking->id }} — {{ $other?->name ?? 'Unknown' }}
                                </option>
                            @endforeach
                        </select>
                        @break

                    @case('select')
                        <select name="{{ $name }}" id="{{ $name }}" class="dsp-select" @required($field['required'] ?? false)>
                            <option value="">Choose one…</option>
                            @foreach($field['options'] ?? [] as $value => $label)
                                <option value="{{ $value }}" @selected(old($name) === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                        @break

                    @case('textarea')
                        <textarea name="{{ $name }}" id="{{ $name }}" class="dsp-area"
                                  @required($field['required'] ?? false)>{{ old($name) }}</textarea>
                        @break

                    @case('checkbox')
                        <label style="display:flex;gap:9px;align-items:flex-start;font-size:13px;line-height:1.55;">
                            <input type="checkbox" name="{{ $name }}" id="{{ $name }}" value="1"
                                   @checked(old($name)) @required($field['required'] ?? false) style="margin-top:2px;">
                            <span>{{ $field['label'] }}</span>
                        </label>
                        @break

                    @case('money')
                        <input type="number" step="0.01" name="{{ $name }}" id="{{ $name }}" class="dsp-input"
                               value="{{ old($name) }}" @required($field['required'] ?? false)>
                        @break

                    @case('number')
                        <input type="number" name="{{ $name }}" id="{{ $name }}" class="dsp-input"
                               value="{{ old($name) }}" @required($field['required'] ?? false)>
                        @break

                    @case('date')
                        <input type="date" name="{{ $name }}" id="{{ $name }}" class="dsp-input"
                               value="{{ old($name) }}" @required($field['required'] ?? false)>
                        @break

                    @case('datetime')
                        <input type="datetime-local" name="{{ $name }}" id="{{ $name }}" class="dsp-input"
                               value="{{ old($name) }}" @required($field['required'] ?? false)>
                        @break

                    @default
                        <input type="text" name="{{ $name }}" id="{{ $name }}" class="dsp-input"
                               value="{{ old($name) }}" @required($field['required'] ?? false)>
                @endswitch

                @if(!empty($field['note']))
                    <p class="dsp-hint">{{ $field['note'] }}</p>
                @endif
                @error($name) <p class="dsp-err">{{ $message }}</p> @enderror
            </div>
        @endforeach

        @if($definition['dual_approval'] ?? false)
            <p class="dsp-hint">
                This is a proposal. Nothing changes until the other party accepts it — their
                existing agreement stands until then.
            </p>
        @endif

        @if($definition['collects_no_bank_details'] ?? false)
            <p class="dsp-hint">
                We never ask for bank or card numbers. Those go straight to the licensed payment
                provider, on their own screens.
            </p>
        @endif

        <div style="margin-top:14px;">
            <button type="submit" class="cl-btn cl-btn-primary">Send</button>
        </div>
    </div>
</form>
@endsection
