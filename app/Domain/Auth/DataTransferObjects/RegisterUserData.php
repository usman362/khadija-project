<?php

namespace App\Domain\Auth\DataTransferObjects;

readonly class RegisterUserData
{
    public function __construct(
        public string $name,
        public string $email,
        public string $password,
        public string $role = 'client',
    ) {
    }
}
