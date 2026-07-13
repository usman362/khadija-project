<?php

namespace App\Providers;

use App\Domain\Auth\Events\UserRegistered;
use App\Domain\Auth\Listeners\LogUserRegistered;
use App\Domain\Auth\Enums\RoleName;
use App\Domain\Messaging\Events\MessageInserted;
use App\Domain\Messaging\Listeners\LogMessageInserted;
use App\Listeners\LogAuthEvents;
use App\Models\Booking as BookingModel;
use App\Models\Conversation as ConversationModel;
use App\Models\Event as EventModel;
use App\Models\Agreement as AgreementModel;
use App\Models\MembershipPlan as MembershipPlanModel;
use App\Models\Message as MessageModel;
use App\Policies\AgreementPolicy;
use App\Policies\BookingPolicy;
use App\Policies\ConversationPolicy;
use App\Policies\EventPolicy;
use App\Policies\MembershipPlanPolicy;
use App\Policies\MessagePolicy;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use App\Domain\Settings\Services\SettingsService;
use Illuminate\Support\ServiceProvider;
use App\Models\User;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(SettingsService::class, fn () => new SettingsService());
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Global pagination: use the self-contained GigResource pager for every
        // bare ->links() call (framework-agnostic, replaces the oversized default).
        \Illuminate\Pagination\Paginator::defaultView('pagination.gr');

        // Public header mega-menu → real top-level categories (with children) that
        // have imagery, so the "All Categories" menu reflects the live taxonomy.
        \Illuminate\Support\Facades\View::composer('partials.navbar', function ($view) {
            $view->with('megaCategories',
                \App\Models\Category::query()
                    ->where('is_active', true)
                    ->whereNull('parent_id')
                    ->whereHas('children')
                    ->with(['children' => fn ($q) => $q->where('is_active', true)->orderBy('name')])
                    ->orderBy('sort_order')->orderBy('name')
                    ->limit(9)
                    ->get()
            );
        });

        Gate::before(function (User $user): ?bool {
            return $user->hasRole(RoleName::ADMIN->value) ? true : null;
        });

        Event::listen(UserRegistered::class, LogUserRegistered::class);
        Event::listen(MessageInserted::class, LogMessageInserted::class);
        Event::subscribe(LogAuthEvents::class);
        Gate::policy(EventModel::class, EventPolicy::class);
        Gate::policy(BookingModel::class, BookingPolicy::class);
        Gate::policy(MessageModel::class, MessagePolicy::class);
        Gate::policy(ConversationModel::class, ConversationPolicy::class);
        Gate::policy(MembershipPlanModel::class, MembershipPlanPolicy::class);
        Gate::policy(AgreementModel::class, AgreementPolicy::class);
    }
}
