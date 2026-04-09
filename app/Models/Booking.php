<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Booking extends Model
{
    use HasFactory;

    protected $fillable = [
        'event_id',
        'client_id',
        'supplier_id',
        'created_by',
        'status',
        'notes',
        'price',
        'currency',
        'booked_at',
        'source',
    ];

    protected function casts(): array
    {
        return [
            'booked_at' => 'datetime',
            'price' => 'decimal:2',
        ];
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(User::class, 'client_id');
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'supplier_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }

    public function conversation(): HasOne
    {
        return $this->hasOne(Conversation::class)->where('type', 'booking');
    }

    public function agreementLogs(): HasMany
    {
        return $this->hasMany(AgreementLog::class, 'subject_id')->where('subject_type', 'booking');
    }

    public function agreements(): HasMany
    {
        return $this->hasMany(Agreement::class);
    }

    public function latestAgreement(): HasOne
    {
        return $this->hasOne(Agreement::class)->latestOfMany('version');
    }

    public function activeAgreement(): HasOne
    {
        return $this->hasOne(Agreement::class)->where('status', '!=', 'rejected')->latestOfMany('version');
    }
}
