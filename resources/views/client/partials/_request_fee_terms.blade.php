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

    UPDATED 20 October 2026. It said "$0 to post. You only pay a single $2.99
    when you finalize", which stopped being true when Peter moved the posting
    fee to publish in his B1 decision and added a separate agreement fee. His
    words: "If a client creates a post it will be $2.99 but if they finally get
    an agreement then the clients $4.99 as well as the others ER and DR."

    So there are two fees now, and both are named here rather than one of them
    arriving as a surprise. The posting figure is read per client, because the
    founder rate and the regular rate are different numbers and quoting the
    wrong one on the page somebody ticks is worse than quoting none.

    $action — what the button does, in the client's words ("post this request")
    $field  — the checkbox's name; the BR has posted `confirm` since before this
              existed and its controller validates that name, so the name is a
              parameter rather than a rename that would break a live form.
--}}
@php
    $rftField = $field ?? 'fee_agreed';
    // From the schedule, which the Pricing and Rates page reads too, so the
    // form and the page cannot quote different numbers.
    $rftPost  = \App\Domain\Money\FeeSchedule::find('posting', auth()->user())['amount'];
    $rftAgree = \App\Domain\Money\FeeSchedule::find('agreement', auth()->user())['amount'];
    $rftMoney = fn (float $n) => '$' . number_format($n, 2);
@endphp
<div class="rft">
    <p class="rft-fee">
        <b>{{ $rftMoney($rftPost) }}</b> to publish this request, charged when it goes out to
        professionals. Drafts are free.<br>
        A further <b>{{ $rftMoney($rftAgree) }}</b> agreement fee applies only if you go on to
        finalize with somebody, or buy a professional's package. Nothing is charged if the request
        goes unfilled.
        {{-- One sentence, from FeeSchedule, so "everywhere" stays the same
             words everywhere. Sir Peter, 20 October: "No refunds or credits
             for if the user cancels their monthly plans, especially for their
             reposts / extension posts, or changes their minds ... this needs to
             be added everywhere." --}}
        {{ \App\Domain\Money\FeeSchedule::refundPolicy() }}
    </p>

    <label class="rft-agree">
        <input type="checkbox" name="{{ $rftField }}" value="1" required>
        <span>
            I understand that {{ $action ?? 'posting this request' }} costs
            <b>{{ $rftMoney($rftPost) }}</b>, and that a further <b>{{ $rftMoney($rftAgree) }}</b>
            applies only if I finalize an agreement or buy a package.
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
