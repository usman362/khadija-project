<?php

namespace App\Domain\Influencer\Enums;

enum ReferralType: string
{
    case SIGNUP_BONUS = 'signup_bonus';
    case BOOKING_COMMISSION = 'booking_commission';
}
