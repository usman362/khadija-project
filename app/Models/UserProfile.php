<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserProfile extends Model
{
    protected $fillable = [
        'user_id',
        'bio',
        'date_of_birth',
        'gender',
        'address',
        'city',
        'state',
        'country',
        'zip_code',
        'website',
        'social_links',
        'company_name',
        'company_website',
        'industry',
        'event_preferences',
        'headline',
        'hourly_rate',
        'availability',
        'skills',
        'experience_years',
        'portfolio',
        'certifications',
        'languages',
        'notify_email_bookings',
        'notify_email_messages',
        'notify_email_events',
        'notify_email_marketing',
    ];

    protected function casts(): array
    {
        return [
            'date_of_birth' => 'date',
            'social_links' => 'array',
            'event_preferences' => 'array',
            'skills' => 'array',
            'portfolio' => 'array',
            'certifications' => 'array',
            'languages' => 'array',
            'hourly_rate' => 'decimal:2',
            'notify_email_bookings' => 'boolean',
            'notify_email_messages' => 'boolean',
            'notify_email_events' => 'boolean',
            'notify_email_marketing' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
