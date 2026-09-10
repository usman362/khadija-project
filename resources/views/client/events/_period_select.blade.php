{{--
    The right rail's period picker. It was a dropdown with one option,
    "This Month", that did nothing — and the numbers under it were all-time.
    It keeps the list's own filters when it reloads, because changing the
    rail should not reset what the client was looking at.
--}}
<form method="GET" action="{{ route('client.events.index') }}" style="margin:0;">
    @foreach(request()->only(['search', 'status', 'category', 'when']) as $k => $v)
        @if($v !== null && $v !== '')<input type="hidden" name="{{ $k }}" value="{{ $v }}">@endif
    @endforeach
    <select name="period" class="mg-rail-sel" aria-label="Period" onchange="this.form.submit()">
        @foreach($periods as $key => $label)
            <option value="{{ $key }}" @selected($period === $key)>{{ $label }}</option>
        @endforeach
    </select>
</form>
