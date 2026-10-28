<?php

namespace App\Domain\Agreements;

use App\Models\AgreementRevision;
use App\Models\Finalization;
use App\Models\User;
use App\Support\DisplayTimezone;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

/**
 * Editing an agreement, under Sir Peter's seven rules.
 *
 * His decision, 11 October: "Replace the existing Request Changes button with
 * Edit Agreement. Do not keep both buttons, because they create overlapping
 * actions and unnecessary confusion."
 *
 * Most of what the rules ask for was already true, because a signature here
 * has always been a signature OF A VERSION: move the terms and the version
 * moves, and the signature simply stops counting. That is rule 2. Signing
 * opening only once both sides approved the same version is rule 4.
 *
 * What this adds is the rest:
 *
 *   1. Either side edits, and every submitted change records who, when, and
 *      WHAT. The old Request Changes recorded only a sentence about it.
 *   3. Once both have signed, the agreement is closed to ordinary edits. A
 *      further change is an amendment: a new version, with the signed one
 *      kept whole.
 *   5. Every version is kept, with its terms, its diff, and who had signed or
 *      approved it at the moment it was superseded.
 *   6. Money cannot move after funding. Reconciling a paid agreement is not
 *      something this system can do, so it says so rather than pretending.
 *   7. A draft is NOT the agreement. Edits sit unsent without touching the
 *      version, so nobody's signature is disturbed until they are submitted.
 */
final class EditAgreement
{
    /** The fields a client may edit, and what each is called on screen. */
    public const FIELDS = [
        'scope'           => 'Scope and deliverables',
        'agreed_price'    => 'Agreed price',
        'service_start'   => 'Service start',
        'service_end'     => 'Service end',
        'schedule_notes'  => 'Schedule notes',
        'deposit_percent' => 'Deposit percentage',
        'balance_due_on'  => 'Balance due on',
        'payment_terms'   => 'Payment terms',
        // Sir Peter, 28 October: the professional may say up front that a
        // staff member might come instead of them, so a later swap is
        // expected rather than a surprise. It is a term, so it travels with
        // the rest: changing it moves the version and both sides look again.
        'staff_may_attend' => 'A staff member may attend',
    ];

    /**
     * Terms that are yes or no rather than a value.
     *
     * They need their own handling in three places: a cleared text box means
     * "not set", but an unticked box means "no", and the two must not be
     * confused. The form sends a hidden 0 before the checkbox so the answer
     * is always present.
     */
    private const FLAGS = ['staff_may_attend'];

    /** Sections, as his edit panel groups them. */
    public const SECTIONS = [
        'Event details & requirements' => ['service_start', 'service_end', 'schedule_notes'],
        'Services & deliverables'      => ['scope'],
        'Budget & payment terms'       => ['agreed_price', 'deposit_percent', 'balance_due_on', 'payment_terms'],
        'Who attends'                  => ['staff_may_attend'],
    ];

    /** Fields that move money, which is why funding closes them. */
    private const FINANCIAL = ['agreed_price', 'deposit_percent', 'balance_due_on', 'payment_terms'];

    /**
     * Fields holding a moment, and fields holding a calendar date.
     *
     * They are apart because they compare differently. A moment is the same
     * moment whatever clock it is written on; a date has no clock at all and
     * belongs to nobody's timezone.
     */
    private const MOMENTS = ['service_start', 'service_end'];
    private const DATES   = ['balance_due_on'];

    /** The terms as they stand, for filling the form. */
    public static function current(Finalization $f): array
    {
        $out = [];

        foreach (array_keys(self::FIELDS) as $field) {
            $out[$field] = $f->{$field};
        }

        return $out;
    }

    /** The unsent draft, if this person has one. */
    public static function draft(Finalization $f): ?array
    {
        return $f->draft_terms ?: null;
    }

    /**
     * Keep edits without touching the agreement.
     *
     * Rule 7. Nothing here moves the version, so a signature already given
     * stays exactly as valid as it was.
     */
    public static function saveDraft(Finalization $f, User $by, array $terms): void
    {
        self::refuseIfClosed($f, $terms);

        $f->forceFill([
            'draft_terms' => self::clean($terms),
            'draft_by'    => $by->id,
            'draft_at'    => now(),
        ])->save();
    }

    public static function discardDraft(Finalization $f): void
    {
        $f->forceFill(['draft_terms' => null, 'draft_by' => null, 'draft_at' => null])->save();
    }

    /**
     * Send the edits, which is the thing that changes the agreement.
     *
     * The version moves, so every signature on the old one stops counting and
     * both sides must look again. The version just left behind is written to
     * the history first, with whatever had been signed or approved on it.
     *
     * @return array<string, array{from: mixed, to: mixed}> what moved
     */
    public static function submit(Finalization $f, User $by, array $terms, ?string $summary = null): array
    {
        $terms = self::clean($terms);
        self::refuseIfClosed($f, $terms);

        $changed = self::diff(self::current($f), $terms);

        if ($changed === []) {
            return [];
        }

        // The outgoing version, kept whole before anything moves.
        self::remember($f, $by, $changed, $summary);

        $f->forceFill($terms + [
            'terms_version'       => Workspace::version($f) + 1,
            'change_request'      => $summary,
            'change_requested_by' => $by->id,
            'change_requested_at' => now(),
            'draft_terms'         => null,
            'draft_by'            => null,
            'draft_at'            => null,
        ])->save();

        return $changed;
    }

    /** Is this term a yes-or-no rather than a value? Asked by the edit panels. */
    public static function isFlag(string $field): bool
    {
        return in_array($field, self::FLAGS, true);
    }

    /** Every version so far, newest first. */
    public static function history(Finalization $f): Collection
    {
        return AgreementRevision::where('finalization_id', $f->id)
            ->with('author:id,name,primary_role')
            ->orderByDesc('version')
            ->get();
    }

    /**
     * What moved since this person last approved, and that they have not
     * approved yet.
     *
     * The history answers "what has ever changed". Somebody looking at an
     * agreement that came back to them is asking a narrower question: what is
     * different from the version I said yes to. Answering the wide question
     * instead makes them read edits they already accepted to find the one
     * they have not.
     *
     * A revision row describes the version it LEFT: its terms, whether this
     * side had approved it, and what was about to move. So the changes since
     * their last approval are the moves recorded from that version onward,
     * and where a field moved twice only the net matters: the value they
     * approved, beside the value in front of them now.
     *
     * @return array<string, array{label?: string, from: mixed, to: mixed}>
     */
    public static function changesSince(Finalization $f, User $person): array
    {
        $side = Workspace::sideOf($f, $person);

        if ($side === null) {
            return [];
        }

        // Approved what is in front of them: nothing is pending their look.
        if ($side === 'client' ? Workspace::clientApproved($f) : Workspace::supplierApproved($f)) {
            return [];
        }

        $column = $side === 'client' ? 'client_approved' : 'supplier_approved';

        $revisions = AgreementRevision::where('finalization_id', $f->id)
            ->orderBy('version')
            ->get();

        $approvedVersion = $revisions->last(fn (AgreementRevision $r) => (bool) $r->{$column})?->version;

        // They have never approved anything, so nothing CHANGED on them. The
        // whole agreement is new to them and the screen shows it in full.
        if ($approvedVersion === null) {
            return [];
        }

        $changed = [];

        foreach ($revisions->where('version', '>=', $approvedVersion) as $rev) {
            foreach ((array) $rev->changed as $field => $move) {
                $changed[$field] = [
                    'label' => $move['label'] ?? ($changed[$field]['label'] ?? $field),
                    // The oldest "from" is the value they approved.
                    'from'  => array_key_exists($field, $changed) ? $changed[$field]['from'] : ($move['from'] ?? null),
                    'to'    => $move['to'] ?? null,
                ];
            }
        }

        return $changed;
    }

    /**
     * Once both have signed, an ordinary edit is an amendment.
     *
     * Rule 3. It is still allowed, and still keeps the signed version whole,
     * but it is worth saying which it is: the screen asks for confirmation
     * and the history records it.
     */
    public static function isAmendment(Finalization $f): bool
    {
        return Workspace::bothSigned($f);
    }

    /**
     * Rule 6, as far as it can honestly go.
     *
     * Once money has moved there is no reconciliation in this system, so a
     * financial change is refused rather than taken and left unsettled.
     */
    private static function refuseIfClosed(Finalization $f, array $terms): void
    {
        if ($f->declined_at) {
            throw ValidationException::withMessages([
                'agreement' => 'This agreement was declined, so it can no longer be edited.',
            ]);
        }

        if (! $f->funded_at) {
            return;
        }

        $moving = array_keys(self::diff(self::current($f), self::clean($terms)));

        if (array_intersect($moving, self::FINANCIAL)) {
            throw ValidationException::withMessages([
                'agreement' => 'The deposit has already been paid, so the price and payment terms '
                    . 'cannot be changed here. Message the professional to agree a refund or a '
                    . 'further payment first.',
            ]);
        }
    }

    /** What moved, field by field, old value beside new. */
    public static function diff(array $before, array $after): array
    {
        $changed = [];

        foreach (self::FIELDS as $field => $label) {
            if (! array_key_exists($field, $after)) {
                continue;
            }

            $was = $before[$field] ?? null;
            $now = $after[$field] ?? null;

            // Loose on purpose: "1800" typed into a form and 1800.00 out of a
            // decimal column are the same agreement, and recording a change
            // nobody made would reset signatures for nothing.
            if (self::same($field, $was, $now)) {
                continue;
            }

            $changed[$field] = ['label' => $label, 'from' => $was, 'to' => $now];
        }

        return $changed;
    }

    /**
     * Whether two values are the same agreement term.
     *
     * The numeric case was here from the start, for the right reason: "1800"
     * typed into a form and 1800.00 out of a decimal column are the same
     * agreement, and recording a change nobody made resets signatures for
     * nothing.
     *
     * Dates had exactly that problem and were not covered. The old value
     * arrives as a Carbon and the new one as the string a person typed, so
     * comparing them as text put "2026-10-31 17:00:00" beside
     * "2026-10-31 17:00" and called the same instant a change. One real
     * agreement in this database has a recorded "Service start" move from
     * 2026-10-31T21:00:00Z to 2026-10-31 17:00, which are the same moment on
     * two different clocks. The version was raised and both sides' approvals
     * stopped counting, over nothing.
     */
    private static function same(string $field, mixed $a, mixed $b): bool
    {
        // A flag has no "not set". Null and false are the same answer, and
        // comparing them as values would report a change nobody made.
        if (in_array($field, self::FLAGS, true)) {
            return (bool) $a === (bool) $b;
        }

        if ($a === null xor $b === null) {
            return false;
        }

        if (in_array($field, self::MOMENTS, true)) {
            $ia = self::instant($a);
            $ib = self::instant($b);

            if ($ia !== null && $ib !== null) {
                return $ia === $ib;
            }
        }

        if (in_array($field, self::DATES, true)) {
            $da = self::calendarDay($a);
            $db = self::calendarDay($b);

            if ($da !== null && $db !== null) {
                return $da === $db;
            }
        }

        if (is_numeric($a) && is_numeric($b)) {
            return abs((float) $a - (float) $b) < 0.005;
        }

        return trim((string) $a) === trim((string) $b);
    }

    /**
     * Whether a written time carries its own timezone.
     *
     * The two values being compared come from different places and are
     * written differently. The stored one is serialised from a Carbon and
     * carries a zone. The new one is what somebody typed into a form, which
     * is a wall clock reading in their own zone and carries nothing. Reading
     * a wall clock as UTC, or a UTC instant as a wall clock, moves a time
     * nobody moved.
     */
    private static function carriesAZone(string $text): bool
    {
        return (bool) preg_match('/(Z|z|[+-]\d{2}:?\d{2})$/', trim($text));
    }

    /** One moment, as a UTC timestamp, or null when it will not parse. */
    private static function instant(mixed $value): ?int
    {
        if ($value instanceof \DateTimeInterface) {
            return $value->getTimestamp();
        }

        $text = trim((string) $value);

        if ($text === '') {
            return null;
        }

        try {
            return self::carriesAZone($text)
                ? Carbon::parse($text)->getTimestamp()
                : Carbon::parse($text, DisplayTimezone::current())->getTimestamp();
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * One calendar day, as Y-m-d.
     *
     * Never shifted onto a clock. A balance due on the 23rd is the 23rd
     * wherever it is read, and converting it is how the 23rd became seven in
     * the evening on the 22nd elsewhere in this application.
     */
    private static function calendarDay(mixed $value): ?string
    {
        if ($value instanceof \DateTimeInterface) {
            return $value->format('Y-m-d');
        }

        $text = trim((string) $value);

        if ($text === '') {
            return null;
        }

        try {
            return Carbon::parse($text)->format('Y-m-d');
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * One recorded value, as a person should read it.
     *
     * A revision stores whatever it was given, so a service time sits in the
     * history as "2026-10-31T21:00:00.000000Z" beside "2026-10-31 17:00".
     * Rows already written cannot be fixed by changing what is stored, so
     * this formats at the point of reading and the old rows read correctly
     * too.
     */
    public static function readable(string $field, mixed $value): string
    {
        if (in_array($field, self::FLAGS, true)) {
            return $value ? 'yes' : 'no';
        }

        if ($value === null || (is_string($value) && trim($value) === '')) {
            return 'not set';
        }

        if (in_array($field, self::DATES, true)) {
            $day = self::calendarDay($value);

            return $day === null ? (string) $value : Carbon::parse($day)->format('M j, Y');
        }

        if (! in_array($field, self::MOMENTS, true)) {
            return (string) $value;
        }

        $at = self::instant($value);

        return $at === null
            ? (string) $value
            : Carbon::createFromTimestamp($at, DisplayTimezone::current())->format('M j, Y · g:i A');
    }

    /** Only the fields we edit, and nothing blank pretending to be a change. */
    private static function clean(array $terms): array
    {
        $out = [];

        foreach (array_keys(self::FIELDS) as $field) {
            if (! array_key_exists($field, $terms)) {
                continue;
            }

            if (in_array($field, self::FLAGS, true)) {
                // "0" is the hidden input the form sends when the box is not
                // ticked, and (bool) "0" is false, which is the answer meant.
                $out[$field] = (bool) $terms[$field];

                continue;
            }

            $value = is_string($terms[$field]) ? trim($terms[$field]) : $terms[$field];
            $out[$field] = $value === '' ? null : $value;
        }

        return $out;
    }

    /** Write the outgoing version into the history before it is replaced. */
    private static function remember(Finalization $f, User $by, array $changed, ?string $summary): void
    {
        AgreementRevision::firstOrCreate(
            ['finalization_id' => $f->id, 'version' => Workspace::version($f)],
            [
                'terms'             => self::current($f),
                'changed'           => $changed,
                'created_by'        => $by->id,
                'summary'           => $summary,
                // What stood on this version at the moment it was superseded.
                'client_signed_at'  => Workspace::clientSigned($f) ? $f->client_signed_at : null,
                'supplier_signed_at' => Workspace::supplierSigned($f) ? $f->supplier_signed_at : null,
                'client_approved'   => Workspace::clientApproved($f),
                'supplier_approved' => Workspace::supplierApproved($f),
            ],
        );
    }
}
