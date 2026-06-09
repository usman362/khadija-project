{{-- Timeline role rows (server render; JS renderRows() mirrors this). --}}
@foreach($roles as $r)
    @php
        $words = preg_split('/\s+/', trim($r['name']));
        $initials = strtoupper(substr($words[0] ?? 'R', 0, 1) . (count($words) > 1 ? substr(end($words), 0, 1) : ''));
    @endphp
    <div class="sp-row">
        <div class="sp-role">
            <span class="sp-avatar" style="background:linear-gradient(135deg, {{ $r['color'] }}, {{ $r['color'] }}cc);">{{ $initials }}</span>
            <div>
                <div class="sp-role-nm">{{ $r['name'] }}</div>
                @if($r['count'] > 1)<div class="sp-role-ct">({{ $r['count'] }} People)</div>@elseif($r['is_you'])<div class="sp-role-ct">(You)</div>@endif
            </div>
        </div>
        <div class="sp-track">
            <div class="sp-bar" style="left:{{ $r['left'] }}%;width:{{ $r['width'] }}%;background:{{ $r['color'] }}26;color:{{ $r['color'] }};">{{ $r['start_label'] }} – {{ $r['end_label'] }}</div>
        </div>
    </div>
@endforeach
