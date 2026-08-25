@props(['files' => collect(), 'draftKey' => '', 'readonly' => false])

{{--
    Files on a request — upload, preview, remove.

    Uploads fire as soon as a file is picked rather than waiting for the step
    to be submitted, because the wizard keeps its state in the session and a
    file that only exists in a form control is a file lost on the next Back.

    Images preview as thumbnails; PDFs open in a tab. Everything else gets a
    labelled tile — an icon that pretends to be a preview is worse than a
    name and a size, which is what the client actually needs to tell two
    floor plans apart.
--}}

@once
@push('styles')
<style>
    .rf-drop { border: 2px dashed var(--border-color); border-radius: 12px; padding: 30px 20px; text-align: center;
               transition: border-color .15s, background .15s; }
    .rf-drop.over { border-color: var(--brand-text, #f97316); background: rgba(249,115,22,0.05); }
    .rf-drop b { display: block; font-size: 14.5px; color: var(--text-primary); margin-bottom: 5px; }
    .rf-drop p { font-size: 12.5px; color: var(--text-muted); line-height: 1.6; max-width: 440px; margin: 0 auto; }
    .rf-pick { display: inline-block; margin-top: 14px; padding: 9px 18px; border-radius: 9px;
               background: var(--brand-text, #f97316); color: #fff; font-size: 13px; font-weight: 700; cursor: pointer; }
    .rf-pick input { display: none; }

    .rf-list { display: grid; grid-template-columns: repeat(auto-fill, minmax(150px, 1fr)); gap: 12px; margin-top: 16px; }
    .rf-tile { border: 1px solid var(--border-color); border-radius: 10px; overflow: hidden; background: var(--bg-card); position: relative; }
    .rf-thumb { display: block; height: 96px; background: rgba(100,116,139,0.07); display: flex; align-items: center;
                justify-content: center; text-decoration: none; overflow: hidden; }
    .rf-thumb img { width: 100%; height: 100%; object-fit: cover; }
    .rf-kind { font-size: 12px; font-weight: 800; color: var(--text-muted); letter-spacing: .04em; }
    .rf-meta { padding: 9px 10px; }
    .rf-name { font-size: 12px; font-weight: 700; color: var(--text-primary); word-break: break-word; line-height: 1.35; }
    .rf-size { font-size: 11px; color: var(--text-muted); margin-top: 3px; }
    .rf-acts { display: flex; gap: 8px; margin-top: 7px; }
    .rf-acts a, .rf-acts button { font-size: 11px; font-weight: 700; text-decoration: none; background: none;
                                  border: 0; padding: 0; cursor: pointer; font-family: inherit; }
    .rf-acts a { color: var(--brand-text, #f97316); }
    .rf-acts button { color: var(--bad-text, #dc2626); }

    .rf-note { font-size: 11.5px; color: var(--text-muted); margin-top: 12px; line-height: 1.55; }
    .rf-err { font-size: 12.5px; color: var(--bad-text, #dc2626); font-weight: 600; margin-top: 10px; }
    .rf-busy { font-size: 12.5px; color: var(--text-muted); font-weight: 600; margin-top: 10px; }
    .rf-empty { font-size: 12.5px; color: var(--text-muted); margin-top: 14px; }
</style>
@endpush
@endonce

<div id="rfRoot"
     data-upload="{{ route('client.request-files.store') }}"
     data-key="{{ $draftKey }}"
     data-readonly="{{ $readonly ? '1' : '0' }}">

    @unless ($readonly)
        <div class="rf-drop" id="rfDrop">
            <b>Drag files here, or choose them</b>
            <p>
                Images, PDF, Word, Excel, CSV or plain text — up to 10 MB each, {{ \App\Http\Controllers\Client\RequestAttachmentController::MAX_FILES }} files.
                Professionals can open these once your request is published.
            </p>
            <label class="rf-pick">
                Choose files
                <input type="file" id="rfInput" multiple
                       accept="image/*,application/pdf,.doc,.docx,.xls,.xlsx,.csv,.txt">
            </label>
            <div class="rf-busy" id="rfBusy" hidden></div>
            <div class="rf-err" id="rfErr" hidden></div>
        </div>
    @endunless

    <div class="rf-list" id="rfList">
        @foreach ($files as $f)
            <div class="rf-tile" data-id="{{ $f->id }}">
                <a class="rf-thumb" href="{{ route('client.request-files.show', [$f, 'inline' => 1]) }}" target="_blank" rel="noopener">
                    @if ($f->isImage())
                        <img src="{{ route('client.request-files.show', [$f, 'inline' => 1]) }}" alt="{{ $f->file_name }}">
                    @else
                        <span class="rf-kind">{{ $f->kind() }}</span>
                    @endif
                </a>
                <div class="rf-meta">
                    <div class="rf-name">{{ $f->file_name }}</div>
                    <div class="rf-size">{{ $f->humanSize() }}</div>
                    <div class="rf-acts">
                        <a href="{{ route('client.request-files.show', $f) }}">Download</a>
                        @unless ($readonly)
                            <button type="button" data-remove="{{ route('client.request-files.destroy', $f) }}">Remove</button>
                        @endunless
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    @if ($files->isEmpty())
        <p class="rf-empty" id="rfEmpty">
            @if ($readonly) No files were attached to this request. @else Nothing attached yet. @endif
        </p>
    @endif

    @unless ($readonly)
        <p class="rf-note">
            Files are stored privately and are not on a public link. Only you can see them until you
            publish — after that, professionals who can bid on this request can open them too.
        </p>
    @endunless
</div>

@unless ($readonly)
@push('scripts')
<script>
(function () {
    var root = document.getElementById('rfRoot');
    if (!root) return;

    var input = document.getElementById('rfInput'),
        drop  = document.getElementById('rfDrop'),
        list  = document.getElementById('rfList'),
        busy  = document.getElementById('rfBusy'),
        err   = document.getElementById('rfErr'),
        empty = document.getElementById('rfEmpty'),
        token = document.querySelector('meta[name="csrf-token"]')?.content;

    function say(el, msg) { if (!el) return; el.textContent = msg || ''; el.hidden = !msg; }

    function tile(f) {
        var el = document.createElement('div');
        el.className = 'rf-tile';
        el.dataset.id = f.id;
        el.innerHTML =
            '<a class="rf-thumb" href="' + f.preview + '" target="_blank" rel="noopener">' +
                (f.is_image
                    ? '<img alt="" src="' + f.preview + '">'
                    : '<span class="rf-kind"></span>') +
            '</a>' +
            '<div class="rf-meta">' +
                '<div class="rf-name"></div>' +
                '<div class="rf-size"></div>' +
                '<div class="rf-acts">' +
                    '<a href="' + f.url + '">Download</a>' +
                    '<button type="button" data-remove="' + f.remove + '">Remove</button>' +
                '</div>' +
            '</div>';
        // Set text through textContent, never innerHTML — the filename is the
        // client's own string and must not be able to write markup here.
        el.querySelector('.rf-name').textContent = f.name;
        el.querySelector('.rf-size').textContent = f.size;
        if (!f.is_image) el.querySelector('.rf-kind').textContent = f.kind;
        return el;
    }

    function upload(files) {
        if (!files || !files.length) return;
        say(err, '');

        var queue = Array.prototype.slice.call(files), done = 0;

        function next() {
            if (!queue.length) { say(busy, ''); return; }
            var file = queue.shift();
            say(busy, 'Uploading ' + file.name + '…' + (queue.length ? ' (' + queue.length + ' to go)' : ''));

            var body = new FormData();
            body.append('file', file);
            body.append('draft_key', root.dataset.key);

            fetch(root.dataset.upload, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': token, 'Accept': 'application/json' },
                body: body,
            })
            .then(function (r) { return r.json().then(function (j) { return { ok: r.ok, body: j }; }); })
            .then(function (res) {
                if (!res.ok) {
                    var m = res.body.message
                        || (res.body.errors && Object.values(res.body.errors)[0][0])
                        || 'That file could not be attached.';
                    say(err, file.name + ' — ' + m);
                } else {
                    if (empty) empty.hidden = true;
                    list.appendChild(tile(res.body.file));
                    done++;
                }
                next();
            })
            .catch(function () {
                say(err, file.name + ' — the upload did not go through. Check your connection and try again.');
                next();
            });
        }

        next();
    }

    input && input.addEventListener('change', function () { upload(this.files); this.value = ''; });

    if (drop) {
        ['dragenter', 'dragover'].forEach(function (e) {
            drop.addEventListener(e, function (ev) { ev.preventDefault(); drop.classList.add('over'); });
        });
        ['dragleave', 'drop'].forEach(function (e) {
            drop.addEventListener(e, function (ev) { ev.preventDefault(); drop.classList.remove('over'); });
        });
        drop.addEventListener('drop', function (ev) { upload(ev.dataTransfer.files); });
    }

    list.addEventListener('click', function (ev) {
        var btn = ev.target.closest('[data-remove]');
        if (!btn) return;

        var tileEl = btn.closest('.rf-tile');
        btn.disabled = true;

        fetch(btn.dataset.remove, {
            method: 'DELETE',
            headers: { 'X-CSRF-TOKEN': token, 'Accept': 'application/json' },
        })
        .then(function (r) { return r.json().then(function (j) { return { ok: r.ok, body: j }; }); })
        .then(function (res) {
            if (!res.ok) { btn.disabled = false; say(err, res.body.message || 'That file could not be removed.'); return; }
            tileEl.remove();
            if (!list.children.length && empty) empty.hidden = false;
        })
        .catch(function () { btn.disabled = false; say(err, 'That file could not be removed.'); });
    });
})();
</script>
@endpush
@endunless
