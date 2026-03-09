<?php

namespace App\Domain\Auth\Contracts;

use App\Domain\Auth\DataTransferObjects\RegisterUserData;
use App\Models\User;

interface UserRegistrationServiceInterface
{
    public function register(RegisterUserData $data): User;
}
