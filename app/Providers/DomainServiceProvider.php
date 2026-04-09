<?php

namespace App\Providers;

use App\Domain\Auth\Contracts\UserRegistrationServiceInterface;
use App\Domain\Auth\Services\EloquentUserRegistrationService;
use App\Domain\Influencer\Contracts\InfluencerServiceInterface;
use App\Domain\Influencer\Events\InfluencerApplied;
use App\Domain\Influencer\Listeners\LogInfluencerApplied;
use App\Domain\Influencer\Services\EloquentInfluencerService;
use App\Models\Booking;
use App\Observers\BookingEmailObserver;
use App\Observers\BookingInfluencerObserver;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class DomainServiceProvider extends ServiceProvider
{
    /**
     * Register domain level services in the container.
     */
    public function register(): void
    {
        $this->app->bind(UserRegistrationServiceInterface::class, EloquentUserRegistrationService::class);
        $this->app->bind(InfluencerServiceInterface::class, EloquentInfluencerService::class);
    }

    public function boot(): void
    {
        Event::listen(InfluencerApplied::class, LogInfluencerApplied::class);
        Booking::observe(BookingInfluencerObserver::class);
        Booking::observe(BookingEmailObserver::class);
    }
}
