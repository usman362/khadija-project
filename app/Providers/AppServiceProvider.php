<?php

namespace App\Providers;

use App\Domain\Auth\Events\UserRegistered;
use App\Domain\Auth\Listeners\LogUserRegistered;
use App\Domain\Auth\Enums\RoleName;
use App\Domain\Messaging\Events\MessageInserted;
use App\Domain\Messaging\Listeners\LogMessageInserted;
use App\Models\Booking as BookingModel;
use App\Models\Event as EventModel;
use App\Models\Message as MessageModel;
use App\Policies\BookingPolicy;
use App\Policies\EventPolicy;
use App\Policies\MessagePolicy;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use App\Models\User;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::before(function (User $user): ?bool {
            return $user->hasRole(RoleName::ADMIN->value) ? true : null;
        });

        Event::listen(UserRegistered::class, LogUserRegistered::class);
        Event::listen(MessageInserted::class, LogMessageInserted::class);
        Gate::policy(EventModel::class, EventPolicy::class);
        Gate::policy(BookingModel::class, BookingPolicy::class);
        Gate::policy(MessageModel::class, MessagePolicy::class);
    }
}
