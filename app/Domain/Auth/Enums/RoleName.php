<?php

namespace App\Domain\Auth\Enums;

enum RoleName: string
{
    case ADMIN = 'admin';
    case CLIENT = 'client';
    case SUPPLIER = 'supplier';
    case INFLUENCER = 'influencer';
}
