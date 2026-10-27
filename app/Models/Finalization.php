<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Concerns\ShowsTimesWhereTheReaderIs;

/**
 * The step-by-step agreement between a client and the professional they chose.
 * See the migration for why each step records who completed it and when.
 */
class Finalization extends Model
{
    use HasFactory;
    use ShowsTimesWhereTheReaderIs;

    /**
     * The datetime fields a person types into a form, which are wall-clock
     * in their own zone rather than instants. See the trait: everything else
     * on this model is written by the application and is already UTC.
     */
    protected array $localWallTimes = ['service_start', 'service_end'];

    protected $guarded = ['id'];

    protected $casts = [
        'bid_reviewed_at'    => 'datetime',
        'scope_agreed_at'    => 'datetime',
        'price_agreed_at'    => 'datetime',
        'schedule_agreed_at' => 'datetime',
        'terms_agreed_at'    => 'datetime',
        'client_signed_at'   => 'datetime',
        'supplier_signed_at' => 'datetime',
        'funded_at'          => 'datetime',
        'service_start'      => 'datetime',
        'service_end'        => 'datetime',
        'balance_due_on'     => 'date',
        'agreed_price'       => 'decimal:2',
        'deposit_amount'     => 'decimal:2',
        'change_requested_at' => 'datetime',
        // Unsent edits. Not the agreement, which is why they live apart from
        // the real columns. See Domain\Agreements\EditAgreement.
        'draft_terms'        => 'array',
        'draft_at'           => 'datetime',
        'declined_at'         => 'datetime',
    ];

    /**
     * Which version of the terms each side approved and signed.
     *
     * See App\Domain\Agreements\Workspace: approval is approval of a
     * version, so that changing a price cannot leave a signature sitting
     * under terms the person never saw.
     */
    public function workspaceStatus(): string
    {
        return \App\Domain\Agreements\Workspace::status($this);
    }

    public function event(): BelongsTo    { return $this->belongsTo(Event::class); }
    public function bid(): BelongsTo      { return $this->belongsTo(Bid::class); }
    public function category(): BelongsTo { return $this->belongsTo(\App\Models\Category::class); }
    public function client(): BelongsTo   { return $this->belongsTo(User::class, 'client_id'); }
    public function supplier(): BelongsTo { return $this->belongsTo(User::class, 'supplier_id'); }
    public function booking(): BelongsTo  { return $this->belongsTo(Booking::class); }
    public function payment(): BelongsTo  { return $this->belongsTo(Payment::class); }

    /** Both parties have signed the contract. */
    public function isSigned(): bool
    {
        return $this->client_signed_at && $this->supplier_signed_at;
    }

    /** Money is secured — in whichever mode the platform was running. */
    public function isFunded(): bool
    {
        return (bool) $this->funded_at;
    }

    /**
     * The step keys in order, mapped to the column that marks each one done.
     * The wizard, the checklist and the progress bar all read this, so they
     * cannot disagree about how far along a finalization is.
     */
    public const STEPS = [
        'bid'      => ['Review Bid',        'bid_reviewed_at'],
        'scope'    => ['Confirm Scope',     'scope_agreed_at'],
        'price'    => ['Price & Fees',      'price_agreed_at'],
        'schedule' => ['Schedule',          'schedule_agreed_at'],
        'terms'    => ['Deposit & Terms',   'terms_agreed_at'],
        'contract' => ['Contract',          'supplier_signed_at'],
        'payment'  => ['Secure Payment',    'funded_at'],
    ];

    public function completed(string $step): bool
    {
        /*
         * The contract step is the one a column cannot answer.
         *
         * It was answered by supplier_signed_at alone, which was true while
         * the client's own signing stamped that column in the professional's
         * name: both signatures arrived together, so either one stood for the
         * pair. The professional signs for themselves from 28 October, and the
         * two no longer arrive together.
         *
         * Read as one column it leaves a real hole. Once the professional has
         * signed, the step counts as done and the deposit step opens, even
         * where the CLIENT has not signed: they could pay for an agreement
         * they never put their name to. Sir Peter, 27 October: "the client
         * should never paid them directly before the agreement".
         *
         * Workspace::bothSigned is also version-aware, so terms changed after
         * signing reopen this step rather than leaving the deposit open on a
         * signature given to terms nobody saw.
         */
        if ($step === 'contract') {
            return \App\Domain\Agreements\Workspace::bothSigned($this);
        }

        $col = self::STEPS[$step][1] ?? null;

        return $col ? (bool) $this->{$col} : false;
    }
}
