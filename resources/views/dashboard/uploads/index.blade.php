@extends('layouts.dashboard')

@section('title', 'Uploaded Files')

@section('content')
@if(session('status'))
    <div class="alert alert-success">{{ session('status') }}</div>
@endif

{{--
    Rule R54's moderation queue. Without somewhere for a held file to go,
    "Manual Review" is a status nobody reads and the file waits for ever.

    The scan line is the important one on this page: while no malware vendor
    is chosen (Open Decisions row 39) every row says NOT SCANNED, and that is
    the truth an admin is releasing a file on. It is not dressed up as a pass.
--}}
<div style="display:flex;align-items:baseline;gap:14px;flex-wrap:wrap;margin-bottom:6px;">
    <h1 style="margin:0;">Uploaded Files</h1>
    <span style="font-size:13px;color:#64748b;">Rule R54 — one pipeline for every upload.</span>
</div>

@if(config('uploads.scanner') === \App\Domain\Uploads\Scanners\UnavailableScanner::class)
    <div class="alert alert-warning" style="margin-bottom:16px;">
        <b>No malware scanner is configured.</b>
        Files are quarantined and checked for type and size, but nothing scans their
        contents. Anything whose purpose holds for review is waiting here for a person.
    </div>
@endif

<div style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:16px;">
    @foreach(['manual_review' => 'Awaiting review', 'approved' => 'Approved', 'rejected' => 'Rejected', 'removed' => 'Removed'] as $key => $label)
        <a href="{{ route('app.admin.uploads.index', ['status' => $key]) }}"
           class="btn {{ $status === $key ? 'btn-primary' : 'btn-secondary' }}">
            {{ $label }} ({{ $counts[$key] ?? 0 }})
        </a>
    @endforeach
</div>

<div class="card">
    <table class="table">
        <thead>
            <tr>
                <th>File</th><th>Purpose</th><th>Uploader</th>
                <th>Scan</th><th>Rights</th><th>Uploaded</th><th></th>
            </tr>
        </thead>
        <tbody>
            @forelse($files as $file)
                <tr>
                    <td>
                        <div style="font-weight:600;">{{ $file->original_name }}</div>
                        <div style="font-size:11.5px;color:#64748b;">
                            {{ $file->mime }} · {{ number_format($file->size / 1024) }} KB
                        </div>
                    </td>
                    <td>{{ config("uploads.purposes.{$file->purpose}.label", $file->purpose) }}</td>
                    <td>
                        {{ $file->uploader?->name ?? '—' }}
                        <div style="font-size:11.5px;color:#64748b;">{{ $file->uploader?->email }}</div>
                    </td>
                    <td>
                        @if($file->scan_status === 'clean')
                            <span style="color:#15803d;font-weight:600;">Clean</span>
                        @elseif($file->scan_status === 'infected')
                            <span style="color:#b91c1c;font-weight:700;">Infected</span>
                        @else
                            <span style="color:#b45309;font-weight:600;">Not scanned</span>
                        @endif
                    </td>
                    <td>
                        {{-- R55 — what the uploader attested, if they were asked. --}}
                        @if($file->rights_attested)
                            <span style="color:#15803d;" title="{{ $file->attestation_text }}">Attested</span>
                        @else
                            <span style="color:#64748b;">—</span>
                        @endif
                    </td>
                    <td style="font-size:12px;color:#64748b;">{{ $file->created_at->diffForHumans() }}</td>
                    <td style="text-align:right;white-space:nowrap;">
                        @if($file->isReleasable())
                            <a class="btn btn-sm btn-secondary" href="{{ route('uploads.show', $file) }}">View</a>
                        @endif

                        @if($file->status === \App\Models\UploadedFile::MANUAL_REVIEW)
                            <form method="POST" action="{{ route('app.admin.uploads.release', $file) }}" style="display:inline;">
                                @csrf<button class="btn btn-sm btn-primary">Release</button>
                            </form>
                            <form method="POST" action="{{ route('app.admin.uploads.reject', $file) }}" style="display:inline;"
                                  onsubmit="this.reason.value = prompt('Why is this being rejected?') || ''; return this.reason.value !== '';">
                                @csrf<input type="hidden" name="reason">
                                <button class="btn btn-sm btn-danger">Reject</button>
                            </form>
                        @elseif($file->status === \App\Models\UploadedFile::APPROVED)
                            {{-- R55 — removable regardless of any consent claimed. --}}
                            <form method="POST" action="{{ route('app.admin.uploads.remove', $file) }}" style="display:inline;"
                                  onsubmit="this.reason.value = prompt('Why is this being removed?') || ''; return this.reason.value !== '';">
                                @csrf<input type="hidden" name="reason">
                                <button class="btn btn-sm btn-danger">Remove</button>
                            </form>
                        @elseif($file->status === \App\Models\UploadedFile::REMOVED)
                            <span style="font-size:12px;color:#64748b;">{{ $file->removal_reason }}</span>
                        @else
                            <span style="font-size:12px;color:#64748b;">{{ $file->decision_reason }}</span>
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="7" style="text-align:center;padding:26px;color:#64748b;">Nothing here.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>

{{ $files->links() }}
@endsection
