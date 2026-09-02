{{--
    What each service is worth, on a request naming more than one.

    Sir Peter, 2026-09-02: BR, DR and ER must all offer this. Bids are per
    service — a professional quotes on ONE of them — so a single total across
    five services showed five different professionals the same $10,000 and had
    each of them price against a figure that was never meant for them.

    Shown only when there is something to divide. One service has one budget,
    and a breakdown field that cannot do anything is worse than none.

    The Bidding Request wizard knows its services by the time it reaches the
    budget step, because it is a wizard. Direct Request and Emergency Request
    are single pages where the client is ticking services in the same form, so
    here the rows are built as they tick — reading the same service-picker
    checkboxes rather than a second list that could disagree with the first.

    Expects:
      $pickerName   string  the service picker's field name (usually "services")
      $split        array   current values, category id => amount, for redisplay
      $suggestUrl   ?string endpoint for "Suggest a split"; omit to hide it
--}}

@php
    $pickerName ??= 'services';
    $split ??= [];
    $suggestUrl ??= null;
@endphp

<div class="sbs" data-sbs data-picker="{{ $pickerName }}" hidden>
    <h4>What is each service worth to you?</h4>
    <p class="sbs-help">
        Professionals quote on one service each, so this is the figure the right
        one sees. Leave any of them blank if you would rather not say.
    </p>

    <div data-sbs-rows></div>

    <div class="sbs-total">
        Breakdown adds up to <b data-sbs-total>—</b>
        @if($suggestUrl)
            {{-- Offered, never applied on its own. It divides the client's own
                 total using the Masterlist's Essential / Common / Occasional
                 ranking; it does not estimate what anything costs, and every
                 box stays editable afterwards. --}}
            <button type="button" class="sbs-suggest" data-sbs-suggest
                    data-url="{{ $suggestUrl }}">Suggest a split</button>
        @endif
        <span class="sbs-note" data-sbs-note></span>
    </div>
</div>

<style>
    .sbs { border:1px solid var(--border-color,#e5e7eb); border-radius:12px; padding:14px 16px; margin-top:14px; }
    .sbs h4 { margin:0 0 4px; font-size:14px; font-weight:800; }
    .sbs-help { margin:0 0 12px; font-size:12.5px; line-height:1.6; color:var(--text-muted,#6b7280); }
    .sbs-row { display:flex; align-items:center; gap:12px; padding:7px 0; }
    .sbs-row label { flex:1; font-size:13.5px; }
    .sbs-row input { width:130px; padding:8px 10px; border:1px solid var(--border-color,#e5e7eb);
                     border-radius:8px; font:inherit; }
    .sbs-total { display:flex; align-items:center; gap:10px; flex-wrap:wrap; margin-top:10px;
                 padding-top:10px; border-top:1px solid var(--border-color,#e5e7eb);
                 font-size:13px; color:var(--text-muted,#6b7280); }
    .sbs-suggest { border:1px solid var(--border-color,#e5e7eb); background:transparent;
                   border-radius:8px; padding:6px 11px; font:inherit; font-size:12.5px; cursor:pointer; }
    .sbs-note { font-size:12px; }
</style>

<script>
(function () {
    const box = document.querySelector('[data-sbs]');
    if (! box) return;

    const rows    = box.querySelector('[data-sbs-rows]');
    const totalEl = box.querySelector('[data-sbs-total]');
    const name    = box.dataset.picker;

    // Values typed before a service was unticked are kept here, so re-ticking
    // it brings the figure back rather than silently discarding what the
    // client entered.
    const remembered = @json((object) $split);

    const boxes = () => Array.from(
        document.querySelectorAll('input[type=checkbox][name="' + name + '[]"]')
    );

    function labelFor(input) {
        const el = input.closest('label')?.querySelector('.svc-text');
        return el ? el.textContent.trim() : ('Service #' + input.value);
    }

    function retotal() {
        const sum = Array.from(rows.querySelectorAll('input'))
            .reduce((t, i) => t + (parseFloat(i.value) || 0), 0);
        totalEl.textContent = sum > 0 ? '$' + sum.toLocaleString() : '—';
    }

    function render() {
        // Remember what is on screen before replacing it.
        rows.querySelectorAll('input').forEach(i => {
            if (i.value !== '') remembered[i.dataset.cat] = i.value;
        });

        const ticked = boxes().filter(b => b.checked);

        // One service has one budget. Nothing to divide, nothing to show.
        box.hidden = ticked.length < 2;

        if (box.hidden) {
            rows.innerHTML = '';
            return;
        }

        rows.innerHTML = '';

        ticked.forEach(b => {
            const row = document.createElement('div');
            row.className = 'sbs-row';

            const label = document.createElement('label');
            label.textContent = labelFor(b);
            label.setAttribute('for', 'sbs-' + b.value);

            const input = document.createElement('input');
            input.type = 'number';
            input.min = '0';
            input.step = '1';
            input.id = 'sbs-' + b.value;
            input.name = 'service_budgets[' + b.value + ']';
            input.placeholder = '—';
            input.dataset.cat = b.value;
            input.value = remembered[b.value] ?? '';
            input.addEventListener('input', retotal);

            row.append(label, input);
            rows.append(row);
        });

        retotal();
    }

    document.addEventListener('change', function (e) {
        if (e.target.matches('input[type=checkbox][name="' + name + '[]"]')) render();
    });

    render();

    /* ── Suggest a split ─────────────────────────────────────── */

    const suggest = box.querySelector('[data-sbs-suggest]');
    const note    = box.querySelector('[data-sbs-note]');

    if (! suggest) return;

    suggest.addEventListener('click', async function () {
        const total = parseFloat(
            document.querySelector('input[name="budget_max"]')?.value
            || document.querySelector('input[name="budget_min"]')?.value
            || ''
        );

        if (! total) {
            note.textContent = 'Enter a budget first.';
            return;
        }

        const ids = boxes().filter(b => b.checked).map(b => b.value);

        suggest.disabled = true;
        note.textContent = 'Working…';

        try {
            const res = await fetch(suggest.dataset.url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]')?.content ?? '',
                    'Accept': 'application/json',
                },
                body: JSON.stringify({ total: total, services: ids }),
            });

            if (! res.ok) throw new Error('HTTP ' + res.status);

            const data = await res.json();

            Object.entries(data.split ?? {}).forEach(([id, amount]) => {
                const input = rows.querySelector('input[data-cat="' + id + '"]');
                if (input) input.value = amount;
            });

            retotal();
            note.textContent = 'Suggested — change anything you like.';
        } catch (err) {
            note.textContent = 'Could not suggest a split just now.';
        } finally {
            suggest.disabled = false;
        }
    });
})();
</script>
