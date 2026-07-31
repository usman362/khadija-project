<?php

namespace App\Domain\Auth\Enums;

enum RoleName: string
{
    case ADMIN = 'admin';
    case CLIENT = 'client';
    case PROFESSIONAL = 'professional';
    case INFLUENCER = 'influencer';
}
