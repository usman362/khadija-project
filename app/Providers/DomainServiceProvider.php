<?php

namespace App\Providers;

use App\Domain\Auth\Contracts\UserRegistrationServiceInterface;
use App\Domain\Auth\Services\EloquentUserRegistrationService;
use Illuminate\Support\ServiceProvider;

class DomainServiceProvider extends ServiceProvider
{
    /**
     * Register domain level services in the container.
     */
    public function register(): void
    {
        $this->app->bind(UserRegistrationServiceInterface::class, EloquentUserRegistrationService::class);
    }
}
