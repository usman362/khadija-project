@props([
    'destination',          // Event (a request) or Agreement
    'canManage' => false,   // the client who placed it — only they may remove
    'showAdd'   => true,    // false where the page already offers its own way in
    'heading'   => 'Planning detail from the toolkit',
])

@php
    $items = \App\Domain\Toolkit\ToolkitBridge::attachmentsOn($destination);
    $isAgreement = $destination instanceof \App\Models\Agreement;
@endphp

{{-- Nothing placed and nobody here to place it: draw nothing rather than an
     empty box explaining a feature this person cannot use. --}}
@if($items->isNotEmpty() || ($canManage && $showAdd))
<div class="tkp">
    <div class="tkp-head">
        <h3>{{ $heading }}</h3>
        @if($canManage && $showAdd)
            <a class="tkp-add" href="{{ route('client.toolkit.plan', ['to' => ($isAgreement ? 'agreement:' : 'request:') . $destination->id]) }}">
                + Add tool data
            </a>
        @endif
    </div>

    @if($isAgreement)
        {{-- Placed data sits ALONGSIDE the contract, never inside it. Rendering
             it among the terms would make a planning figure look like something
             both parties signed. --}}
        <p class="tkp-note">Context the client attached for this work. It is not part of the agreement text above.</p>
    @endif

    @if($items->isEmpty())
        <p class="tkp-empty">Nothing attached yet. You can add a budget, timeline or checklist you already worked out in the toolkit.</p>
    @else
        @foreach($items as $item)
            <div class="tkp-item">
                <div class="tkp-main">
                    <div class="tkp-title">
                        {{ $item->title }}
                        @if($item->needs_review)
                            <span class="tkp-flag">Update waiting</span>
                        @endif
                    </div>
                    {{-- Labelled with the tool and the moment, so a figure here
                         can always be traced back to what produced it. --}}
                    <div class="tkp-meta">
                        From {{ $item->tool_name }} · added {{ $item->created_at->format('M j, Y') }}
                        @if($item->isLinked()) · linked to the original @endif
                    </div>

                    @if(is_array($item->payload) && count($item->payload))
                        <dl class="tkp-fields">
                            @foreach(array_slice($item->payload, 0, 6, true) as $k => $v)
                                <div>
                                    <dt>{{ ucfirst(str_replace('_', ' ', (string) $k)) }}</dt>
                                    {{-- Was implode(', ', array_map('strval', $v)), which
                                         assumes every element is a scalar. A tool that
                                         saves a list of ROWS — a checklist, a timeline —
                                         hands it an array of arrays, and strval() on an
                                         array is fatal. It took a whole event page down. --}}
                                    <dd>{{ \App\Support\PayloadPreview::line($v) }}</dd>
                                </div>
                            @endforeach
                        </dl>
                        @if(count($item->payload) > 6)
                            <div class="tkp-more">+{{ count($item->payload) - 6 }} more in the toolkit</div>
                        @endif
                    @endif
                </div>

                @if($canManage)
                    <form method="POST" action="{{ route('client.toolkit.placed.destroy', $item) }}"
                          onsubmit="return confirm('Remove this? Your saved toolkit result is kept.');">
                        @csrf @method('DELETE')
                        <button class="tkp-remove" type="submit">Remove</button>
                    </form>
                @endif
            </div>
        @endforeach
    @endif
</div>

<style>
    .tkp { border: 1px solid rgba(128,128,128,.28); border-radius: 12px; padding: 16px 18px; margin: 18px 0; }
    .tkp-head { display: flex; align-items: center; justify-content: space-between; gap: 12px; flex-wrap: wrap; }
    .tkp-head h3 { font-size: 15px; font-weight: 800; margin: 0; }
    .tkp-add { font-size: 12.5px; font-weight: 700; text-decoration: none; padding: 6px 12px;
               border: 1px solid rgba(128,128,128,.35); border-radius: 20px; white-space: nowrap; }
    .tkp-note, .tkp-empty { font-size: 12.5px; opacity: .72; margin: 6px 0 0; }
    .tkp-item { display: flex; justify-content: space-between; align-items: flex-start; gap: 14px;
                padding: 13px 0; border-bottom: 1px solid rgba(128,128,128,.18); }
    .tkp-item:last-child { border-bottom: 0; padding-bottom: 0; }
    .tkp-title { font-size: 13.5px; font-weight: 700; }
    .tkp-flag { display: inline-block; font-size: 10px; font-weight: 800; text-transform: uppercase;
                letter-spacing: .04em; padding: 2px 7px; border-radius: 20px;
                background: rgba(245,158,11,.16); color: #b45309; margin-left: 5px; }
    .tkp-meta { font-size: 11.5px; opacity: .68; margin-top: 3px; }
    .tkp-fields { display: grid; grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
                  gap: 8px 16px; margin: 9px 0 0; }
    .tkp-fields dt { font-size: 10.5px; text-transform: uppercase; letter-spacing: .04em; opacity: .6; margin: 0; }
    .tkp-fields dd { font-size: 13px; font-weight: 600; margin: 1px 0 0; }
    .tkp-more { font-size: 11.5px; opacity: .62; margin-top: 7px; }
    .tkp-remove { border: 1px solid rgba(128,128,128,.35); background: transparent; color: inherit;
                  border-radius: 7px; padding: 5px 10px; font-size: 12px; cursor: pointer;
                  font-family: inherit; flex-shrink: 0; }
</style>
@endif
