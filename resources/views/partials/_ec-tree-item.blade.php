{{--
    Public category-tree node. Mirrors the admin tree, but every row is a
    public drill-in link (?in=<slug>) rather than an admin edit link, and a
    node with children carries its own disclosure toggle so 360 categories
    stay navigable.

    Expects: $categories (collection), $depth (int), $branch (?Category)
--}}
@foreach($categories as $cat)
    @php
        $kids     = $cat->allChildren ?? collect();
        $isActive = $branch && $branch->id === $cat->id;
    @endphp
    <div class="ec-tree-node {{ $depth > 0 ? 'ec-tree-nested' : '' }}">
        <div class="ec-tree-row {{ $isActive ? 'active' : '' }}">
            @if($kids->count())
                <button type="button" class="ec-tree-toggle" aria-expanded="false" aria-label="Toggle {{ $cat->name }}">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6"><polyline points="9 18 15 12 9 6"/></svg>
                </button>
            @else
                <span class="ec-tree-toggle ec-tree-leaf"></span>
            @endif
            <a class="ec-tree-link" href="{{ route('events-categories', ['in' => $cat->slug]) }}#ec-browse">
                <svg class="ec-tree-ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    @if($kids->count())
                        <path d="M3 7a2 2 0 0 1 2-2h4l2 2h8a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>
                    @else
                        <path d="M14 3H7a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V8z"/><polyline points="14 3 14 8 19 8"/>
                    @endif
                </svg>
                <span>{{ $cat->name }}</span>
            </a>
        </div>
        @if($kids->count())
            <div class="ec-tree-kids" hidden>
                @include('partials._ec-tree-item', ['categories' => $kids, 'depth' => $depth + 1, 'branch' => $branch])
            </div>
        @endif
    </div>
@endforeach
