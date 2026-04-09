<?php

namespace App\Domain\Influencer\Enums;

enum ReferralStatus: string
{
    case PENDING = 'pending';
    case EARNED = 'earned';
    case PAID = 'paid';
    case CANCELLED = 'cancelled';
}
