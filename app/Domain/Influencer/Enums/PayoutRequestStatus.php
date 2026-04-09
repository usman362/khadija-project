<?php

namespace App\Domain\Influencer\Enums;

enum PayoutRequestStatus: string
{
    case PENDING = 'pending';
    case APPROVED = 'approved';
    case PAID = 'paid';
    case REJECTED = 'rejected';
}
