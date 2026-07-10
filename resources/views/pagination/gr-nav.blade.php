{{-- GigResource pagination — small, neat page buttons. Replaces Laravel's
     default Tailwind view (which renders oversized unstyled chevrons on this
     non-Tailwind app). --}}
@if ($paginator->hasPages())
<nav class="gr-pag" role="navigation" aria-label="Pagination">
    @if ($paginator->onFirstPage())
        <span class="gr-pag-btn is-disabled" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="15 18 9 12 15 6"/></svg>
        </span>
    @else
        <a class="gr-pag-btn" href="{{ $paginator->previousPageUrl() }}" rel="prev" aria-label="Previous">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="15 18 9 12 15 6"/></svg>
        </a>
    @endif

    @foreach ($elements as $element)
        @if (is_string($element))
            <span class="gr-pag-btn is-dots">{{ $element }}</span>
        @endif
        @if (is_array($element))
            @foreach ($element as $page => $url)
                @if ($page == $paginator->currentPage())
                    <span class="gr-pag-btn is-active" aria-current="page">{{ $page }}</span>
                @else
                    <a class="gr-pag-btn" href="{{ $url }}">{{ $page }}</a>
                @endif
            @endforeach
        @endif
    @endforeach

    @if ($paginator->hasMorePages())
        <a class="gr-pag-btn" href="{{ $paginator->nextPageUrl() }}" rel="next" aria-label="Next">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="9 18 15 12 9 6"/></svg>
        </a>
    @else
        <span class="gr-pag-btn is-disabled" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="9 18 15 12 9 6"/></svg>
        </span>
    @endif
</nav>
@endif
