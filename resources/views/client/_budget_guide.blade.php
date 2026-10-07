{{--
    What professionals have actually charged, beside the box asking what you
    will pay.

    Sir Peter, 26 September: "can get a rough estimator for the the clients so
    they are not over guessing or something so its more in tune with the
    prices lists of the current professionals in our databases."

    The Bidding Request prints this on the server, because by the time it asks
    about money the services were settled on an earlier step. The Emergency
    and Direct Requests ask both questions on one page, so this fills itself
    in as services are ticked.

    It says nothing the printed version would not, including why it has
    nothing to say. The reasoning is in App\Domain\Requests\BudgetGuide; this
    only shows what that decided.
--}}
@once
@push('styles')
<style>
    .bg-guide { margin: 10px 0 0; padding: 11px 14px; border-radius: 11px; font-size: 12.5px; line-height: 1.55;
        background: var(--bg-card-hover, #f8fafc); border: 1px solid var(--border-color); color: var(--text-secondary); }
    .bg-guide[hidden] { display: none; }
    .bg-guide b { color: var(--text-primary); }
    .bg-guide ul { margin: 6px 0 0; padding-left: 18px; }
    .bg-guide li { margin-top: 3px; }
    .bg-guide-foot { display: block; margin-top: 7px; font-size: 11.5px; color: var(--text-muted); }
</style>
@endpush
@endonce

<div class="bg-guide" data-budget-guide hidden></div>

@once
@push('scripts')
<script>
(function () {
    var box = document.querySelector('[data-budget-guide]');
    if (! box) return;

    var URL_ = @json(route('client.budget-guide'));
    var last = '';

    function picked() {
        return Array.prototype.map.call(
            document.querySelectorAll('.svc-item input:checked'),
            function (i) { return i.value; }
        ).filter(Boolean);
    }

    function esc(t) {
        var d = document.createElement('div');
        d.textContent = t == null ? '' : String(t);
        return d.innerHTML;
    }

    function draw(rows) {
        if (! rows.length) { box.hidden = true; return; }

        // One service gets a sentence; several get a line each, because a
        // paragraph joining four of them reads as one claim about all four.
        var html = rows.length === 1
            ? esc(rows[0].sentence)
            : '<b>What others have charged</b><ul>' + rows.map(function (r) {
                  return '<li>' + esc(r.name) + ': ' + esc(
                      r.has ? '$' + Math.round(r.low).toLocaleString() + ' to $' + Math.round(r.high).toLocaleString()
                              + ', usually $' + Math.round(r.typical).toLocaleString()
                            : 'no bid history yet'
                  ) + '</li>';
              }).join('') + '</ul>';

        if (rows.some(function (r) { return r.has; })) {
            html += '<span class="bg-guide-foot">A guide from real bids, not a quote.</span>';
        }

        box.innerHTML = html;
        box.hidden = false;
    }

    function sync() {
        var ids = picked();
        var key = ids.join(',');

        if (key === last) return;
        last = key;

        if (! ids.length) { box.hidden = true; return; }

        fetch(URL_ + '?' + ids.map(function (i) { return 'services[]=' + encodeURIComponent(i); }).join('&'),
              { credentials: 'same-origin', headers: { 'Accept': 'application/json' } })
            .then(function (r) { return r.ok ? r.json() : null; })
            .then(function (d) { if (d) draw(d.services || []); })
            .catch(function () { /* a guide that cannot load says nothing, rather than something wrong. */ });
    }

    document.addEventListener('change', function (e) {
        if (e.target.matches && e.target.matches('.svc-item input')) sync();
    });

    sync();
})();
</script>
@endpush
@endonce
