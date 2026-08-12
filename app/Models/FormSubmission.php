<?php

namespace App\Models;

use App\Domain\Forms\FormRegistry;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/** One filled-in form. See FormRegistry for what each form's fields are. */
class FormSubmission extends Model
{
    protected $fillable = [
        'form_key', 'submitted_by', 'submitted_role', 'subject_type', 'subject_id',
        'payload', 'counterparty_id', 'approval_status', 'approval_note', 'approved_at',
        'status', 'resolution_note', 'certification_text',
    ];

    protected function casts(): array
    {
        return [
            'payload'     => 'array',
            'approved_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $submission) {
            $submission->reference ??= self::nextReference();
        });
    }

    public static function nextReference(): string
    {
        $last = static::query()->orderByDesc('id')->lockForUpdate()->value('reference');

        return sprintf('FS-%s-%06d', now()->year, $last ? ((int) substr($last, -6)) + 1 : 1);
    }

    public function definition(): ?array
    {
        return FormRegistry::get($this->form_key);
    }

    public function title(): string
    {
        return $this->definition()['title'] ?? ucwords(str_replace('_', ' ', $this->form_key));
    }

    /** A change order is a proposal until the other side accepts it. */
    public function needsApproval(): bool
    {
        return (bool) ($this->definition()['dual_approval'] ?? false);
    }

    public function isAccepted(): bool
    {
        return $this->approval_status === 'accepted';
    }

    /**
     * The answers, paired with the labels they were asked under.
     *
     * Read from the definition rather than stored per submission: the label
     * is how the question was worded, and a payload of bare keys is unreadable
     * to whoever has to action it six weeks later.
     *
     * @return array<int, array{label:string, value:mixed}>
     */
    public function answers(): array
    {
        $definition = $this->definition();

        if ($definition === null) {
            return [];
        }

        $out = [];

        foreach ($definition['fields'] as $field) {
            if (($field['type'] ?? null) === 'certification') {
                continue;   // shown separately, with its stored wording
            }

            $value = $this->payload[$field['name']] ?? null;

            if ($value === null || $value === '') {
                continue;
            }

            $out[] = ['label' => $field['label'] ?? $field['name'], 'value' => $value];
        }

        return $out;
    }

    public function submitter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    public function counterparty(): BelongsTo
    {
        return $this->belongsTo(User::class, 'counterparty_id');
    }

    public function subject(): MorphTo
    {
        return $this->morphTo();
    }
}
