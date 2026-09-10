{{--
    What posting a request costs, and the agreement to it.

    Sir Peter, 2026-09-08: the BR, ER and DR must all say the same thing about
    the fee, and the client must actively agree to it before the request goes
    anywhere. It was on the ER only, worded there and nowhere else, and the BR
    carried its own three separate wordings — which is how three pages end up
    quoting three different fees the first time one of them changes.

    One partial, so the wording cannot drift. The checkbox is required by the
    controller that receives the form, not only by the browser: a page that
    only says "required" is a page that can be posted around.

    $action — what the button does, in the client's words ("post this request")
    $field  — the checkbox's name; the BR has posted `confirm` since before this
              existed and its controller validates that name, so the name is a
              parameter rather than a rename that would break a live form.
--}}
@php $rftField = $field ?? 'fee_agreed'; @endphp
<div class="rft">
    <p class="rft-fee">
        <b>$0</b> to post. You only pay a single <b>$2.99</b> when you finalize
        with a professional.<br>
        Nothing is charged to post, and nothing if the request goes unfilled.
    </p>

    <label class="rft-agree">
        <input type="checkbox" name="{{ $rftField }}" value="1" required>
        <span>
            I understand that {{ $action ?? 'posting this request' }} is free, and that a single
            <b>$2.99</b> fee applies only if I finalize an agreement with a professional.
        </span>
    </label>
</div>

@once
@push('styles')
<style>
    .rft { margin: 0 0 12px; }
    .rft-fee { font-size: 12px; line-height: 1.6; color: var(--text-muted, #6b7280); margin: 0 0 10px; }
    .rft-fee b { color: var(--text-primary, #111827); font-weight: 800; }
    .rft-agree { display: flex; align-items: flex-start; gap: 9px; font-size: 12.5px;
                 line-height: 1.55; color: var(--text-secondary, #374151);
                 border: 1px solid var(--border-color, #e5e7eb); border-radius: 10px;
                 padding: 10px 12px; cursor: pointer; }
    .rft-agree input { margin-top: 2px; flex: none; width: auto; }
    /* The label is one sentence. Without this the checkbox row is a flex
       container and every <b> inside becomes its own flex item, which puts a
       gap either side of the amount and breaks the line there. */
    .rft-agree span { display: block; }
</style>
@endpush
@endonce
