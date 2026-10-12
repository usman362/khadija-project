<?php

namespace App\Domain\Agreements;

use App\Models\AgreementRevision;
use App\Models\Finalization;
use App\Models\User;
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
    ];

    /** Sections, as his edit panel groups them. */
    public const SECTIONS = [
        'Event details & requirements' => ['service_start', 'service_end', 'schedule_notes'],
        'Services & deliverables'      => ['scope'],
        'Budget & payment terms'       => ['agreed_price', 'deposit_percent', 'balance_due_on', 'payment_terms'],
    ];

    /** Fields that move money, which is why funding closes them. */
    private const FINANCIAL = ['agreed_price', 'deposit_percent', 'balance_due_on', 'payment_terms'];

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

    /** Every version so far, newest first. */
    public static function history(Finalization $f): Collection
    {
        return AgreementRevision::where('finalization_id', $f->id)
            ->with('author:id,name,primary_role')
            ->orderByDesc('version')
            ->get();
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
            if (self::same($was, $now)) {
                continue;
            }

            $changed[$field] = ['label' => $label, 'from' => $was, 'to' => $now];
        }

        return $changed;
    }

    private static function same(mixed $a, mixed $b): bool
    {
        if ($a === null xor $b === null) {
            return false;
        }

        if (is_numeric($a) && is_numeric($b)) {
            return abs((float) $a - (float) $b) < 0.005;
        }

        return trim((string) $a) === trim((string) $b);
    }

    /** Only the fields we edit, and nothing blank pretending to be a change. */
    private static function clean(array $terms): array
    {
        $out = [];

        foreach (array_keys(self::FIELDS) as $field) {
            if (array_key_exists($field, $terms)) {
                $value = is_string($terms[$field]) ? trim($terms[$field]) : $terms[$field];
                $out[$field] = $value === '' ? null : $value;
            }
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
