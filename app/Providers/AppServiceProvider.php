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

        // Rule R54 — the malware scanner behind the upload pipeline. Bound
        // from config so choosing a vendor (Open Decisions row 39) is one env
        // line rather than a change to every upload path. Until then the
        // bound scanner reports NOT SCANNED, never clean.
        $this->app->bind(
            \App\Domain\Uploads\Contracts\MalwareScanner::class,
            fn () => app(config('uploads.scanner', \App\Domain\Uploads\Scanners\UnavailableScanner::class)),
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Global pagination: use the self-contained GigResource pager for every
        // bare ->links() call (framework-agnostic, replaces the oversized default).
        \Illuminate\Pagination\Paginator::defaultView('pagination.gr');

        /*
         * Checklist row 146 — one relative time, rounded rather than truncated.
         *
         * Carbon reports the largest WHOLE unit, so 54 days reads "1 month
         * ago" — technically one month and twenty-four days, but a reader
         * takes it as about a month and is out by nearly four weeks. It was
         * spotted on a notification whose event was 54 days old.
         *
         * ->humanAgo() rounds to the nearest unit instead, so 54 days reads
         * "2 months ago". A macro rather than a helper function because every
         * caller already has a Carbon instance in hand.
         *
         * NOT named ago(): Carbon already defines a real ago() method, and a
         * real method always wins over a macro — the macro would be registered,
         * report itself present, and never once be called.
         */
        \Illuminate\Support\Carbon::macro('humanAgo', function (...$args) {
            /** @var \Illuminate\Support\Carbon $this */
            $options = ['options' => \Carbon\CarbonInterface::ROUND];

            if ($args !== [] && $args[count($args) - 1] === true) {
                $options['syntax'] = \Carbon\CarbonInterface::DIFF_ABSOLUTE;
            }

            return $this->diffForHumans($options);
        });

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
