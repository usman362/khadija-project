{{-- Staff list rows (server render; JS renderList() mirrors this). --}}
@foreach($roles as $r)
    @php $dur = round($r['end'] - $r['start'], 1); @endphp
    <tr>
        <td><div class="role"><span class="dot" style="background:{{ $r['color'] }};"></span>{{ $r['name'] }}@if($r['count'] > 1) ({{ $r['count'] }})@endif</div></td>
        <td>{{ $r['count'] }}</td>
        <td>{{ $r['start_label'] }}</td>
        <td>{{ $r['end_label'] }} <span style="color:var(--text-muted);">· {{ $dur }}h</span></td>
    </tr>
@endforeach
