@once
    <style>
        .grpag { display:flex; flex-direction:column; align-items:center; gap:.6rem; margin:1.25rem 0; }
        .grpag-info { color:#64748b; font-size:.82rem; margin:0; }
        .grpag-info strong { color:#94a3b8; font-weight:700; }
        .grpag-list { display:flex; flex-wrap:wrap; gap:6px; list-style:none; padding:0; margin:0; justify-content:center; }
        .grpag-item > a, .grpag-item > span {
            min-width:40px; height:40px; display:flex; align-items:center; justify-content:center;
            padding:0 12px; border-radius:10px; border:1px solid rgba(148,163,184,.20);
            color:#64748b; font-weight:600; font-size:.85rem; line-height:1; text-decoration:none;
            transition:all .15s ease; user-select:none;
        }
        .grpag-item > a:hover { background:rgba(99,102,241,.15); border-color:rgba(99,102,241,.5); color:#6366f1; }
        .grpag-item.active > span {
            background:linear-gradient(135deg,#6366f1,#8b5cf6); border-color:transparent; color:#fff;
            box-shadow:0 4px 14px rgba(99,102,241,.45);
        }
        .grpag-item.disabled > span { opacity:.4; }
    </style>
@endonce

@if ($paginator->hasPages())
    <nav class="grpag" role="navigation" aria-label="Pagination Navigation">
        <p class="grpag-info">
            Showing <strong>{{ $paginator->firstItem() }}</strong>
            to <strong>{{ $paginator->lastItem() }}</strong>
            of <strong>{{ $paginator->total() }}</strong> results
        </p>
        <ul class="grpag-list">
            {{-- Previous --}}
            @if ($paginator->onFirstPage())
                <li class="grpag-item disabled" aria-disabled="true"><span>&lsaquo;</span></li>
            @else
                <li class="grpag-item"><a href="{{ $paginator->previousPageUrl() }}" rel="prev" aria-label="Previous">&lsaquo;</a></li>
            @endif

            {{-- Page numbers --}}
            @foreach ($elements as $element)
                @if (is_string($element))
                    <li class="grpag-item disabled" aria-disabled="true"><span>{{ $element }}</span></li>
                @endif
                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <li class="grpag-item active" aria-current="page"><span>{{ $page }}</span></li>
                        @else
                            <li class="grpag-item"><a href="{{ $url }}">{{ $page }}</a></li>
                        @endif
                    @endforeach
                @endif
            @endforeach

            {{-- Next --}}
            @if ($paginator->hasMorePages())
                <li class="grpag-item"><a href="{{ $paginator->nextPageUrl() }}" rel="next" aria-label="Next">&rsaquo;</a></li>
            @else
                <li class="grpag-item disabled" aria-disabled="true"><span>&rsaquo;</span></li>
            @endif
        </ul>
    </nav>
@endif
