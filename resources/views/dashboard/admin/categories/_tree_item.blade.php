@foreach($categories as $cat)
    <div class="mb-1" style="{{ $depth > 0 ? 'margin-left: 16px; border-left: 1px solid rgba(255,255,255,0.08); padding-left: 10px;' : '' }}">
        <div class="d-flex align-items-center py-1">
            <i data-lucide="{{ $cat->icon ?: 'folder' }}"
               class="{{ $depth === 0 ? 'text-warning' : ($depth === 1 ? 'text-info' : 'text-secondary') }} me-2"
               style="width:{{ max(12, 16 - $depth * 2) }}px; height:{{ max(12, 16 - $depth * 2) }}px; flex-shrink:0;"></i>
            <span style="font-size: {{ $depth === 0 ? '0.9rem' : '0.82rem' }}; {{ $depth === 0 ? 'font-weight:600;' : '' }}">{{ $cat->name }}</span>
        </div>
        @if($cat->allChildren && $cat->allChildren->count())
            @include('dashboard.admin.categories._tree_item', ['categories' => $cat->allChildren, 'depth' => $depth + 1])
        @endif
    </div>
@endforeach
