<?php

namespace App\Domain\Auth\Services;

use App\Domain\Auth\Contracts\UserRegistrationServiceInterface;
use App\Domain\Auth\DataTransferObjects\RegisterUserData;
use App\Domain\Auth\Enums\RoleName;
use App\Domain\Auth\Events\UserRegistered;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class EloquentUserRegistrationService implements UserRegistrationServiceInterface
{
    public function register(RegisterUserData $data): User
    {
        return DB::transaction(function () use ($data): User {
            $user = User::query()->create([
                'name' => $data->name,
                'email' => $data->email,
                'password' => Hash::make($data->password),
            ]);

            // Assign role based on registration choice (client or supplier)
            $role = $data->role === 'supplier'
                ? RoleName::SUPPLIER->value
                : RoleName::CLIENT->value;

            $user->assignRole($role);

            UserRegistered::dispatch($user);

            return $user;
        });
    }
}
