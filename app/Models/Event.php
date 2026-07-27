<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Event extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'budget',
        'location',
        'venue',
        'guest_count',
        'media',
        'status',
        'is_published',
        'published_at',
        'starts_at',
        'ends_at',
        'created_by',
        'client_id',
        'supplier_id',
        'source',
        'category_id',
        // BSR wizard fields
        'event_type',
        'organization_type',
        'characteristic',
        'budget_min',
        'budget_max',
        'proposal_deadline',
        'sealed_proposals',
        'questions_enabled',
    ];

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'published_at' => 'datetime',
            'is_published' => 'boolean',
            'budget' => 'decimal:2',
            'guest_count' => 'integer',
            'media' => 'array',
            'proposal_deadline' => 'datetime',
            'budget_min' => 'decimal:2',
            'budget_max' => 'decimal:2',
            'sealed_proposals' => 'boolean',
            'questions_enabled' => 'boolean',
        ];
    }

    /** @deprecated Use categories() instead */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class)->withTimestamps();
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(User::class, 'client_id');
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'supplier_id');
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    /** AI-tool results the client saved onto this event ("Add to my event"). */
    public function aiArtifacts(): HasMany
    {
        return $this->hasMany(EventAiArtifact::class)->latest();
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }
}
