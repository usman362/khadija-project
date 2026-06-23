@once
@push('scripts')
<script>
document.addEventListener('click', function (e) {
    var btn = e.target.closest('[data-copy]');
    if (!btn) return;
    var el = document.querySelector(btn.getAttribute('data-copy'));
    if (!el) return;
    var text = el.value !== undefined ? el.value : el.textContent;
    navigator.clipboard.writeText(text.trim()).then(function () {
        var original = btn.textContent;
        btn.textContent = 'Copied!';
        setTimeout(function () { btn.textContent = original; }, 1600);
    });
});
</script>
@endpush
@endonce
