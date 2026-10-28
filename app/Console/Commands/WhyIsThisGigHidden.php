<?php

namespace App\Console\Commands;

use App\Domain\Requests\RequestLifecycle;
use App\Models\Event;
use App\Models\User;
use App\Support\RadiusMatching;
use App\Support\StateMatching;
use Illuminate\Console\Command;

/**
 * Why a gig is, or is not, on one professional's bidding board.
 *
 * Ali, 28 October, twice: a request was posted and no professional could bid
 * on it. Both times the answer was a different gate, and both times finding it
 * meant reading four classes. The board is a stack of filters that each hide
 * what the viewer cannot act on, which is right, and the cost of being right
 * that way is that a hidden gig looks identical to a broken one.
 *
 * This walks the same gates in the same order and says which one closed. It
 * reads and reports; it changes nothing.
 */
class WhyIsThisGigHidden extends Command
{
    protected $signature = 'gig:why-hidden
                            {event : The event id}
                            {professional : The professional\'s email}';

    protected $description = 'Say which filter is keeping a gig off a professional\'s bidding board';

    public function handle(): int
    {
        $event = Event::with('categories:id,name')->find($this->argument('event'));
        $pro   = User::where('email', $this->argument('professional'))->first();

        if (! $event) {
            $this->error("No event with id {$this->argument('event')}.");

            return self::FAILURE;
        }

        if (! $pro) {
            $this->error("No account with the email {$this->argument('professional')}.");

            return self::FAILURE;
        }

        $this->line("Gig:          #{$event->id} {$event->title}");
        $this->line('Services:     ' . ($event->categories->pluck('name')->implode(', ') ?: 'none named'));
        $this->line('Location:     ' . ($event->location ?: 'not set'));
        $this->line('Event date:   ' . ($event->starts_at?->format('M j, Y · g:i A') ?? 'not set'));
        $this->line("Professional: {$pro->name} <{$pro->email}>, " . ($pro->profile?->state ?? 'no state'));
        $this->newLine();

        $blocked = false;

        /*
         * The reason goes on its own line rather than in a column.
         *
         * Some of these run to 140 characters, which wraps in the control
         * panel's browser terminal, and that is where this will actually be
         * read. A wrapped column reads as two broken rows.
         */
        foreach ($this->gates($event, $pro) as [$name, $open, $why, $advice]) {
            $this->line(sprintf(
                ' %s  %s',
                $open ? '<fg=green>PASS</>' : '<fg=red>STOP</>',
                $name,
            ));

            // Wrapped by hand, short lines, because every one of these ends up
            // being read in the control panel's browser terminal.
            foreach (array_filter([$why, $open ? null : $advice]) as $sentence) {
                $this->line('       ' . $sentence);
            }

            if (! $open) {
                $blocked = true;
            }
        }

        $this->newLine();

        if ($blocked) {
            $this->warn('This gig is not on that professional\'s board. The STOP lines above are why.');
        } else {
            $this->info('Nothing is hiding this gig. It should be on their board with Place Bid open.');
        }

        return self::SUCCESS;
    }

    /**
     * Each gate, in the order the board applies them.
     *
     * @return list<array{0: string, 1: bool, 2: string, 3: ?string}>
     */
    private function gates(Event $event, User $pro): array
    {
        $status = RequestLifecycle::statusFor($event);

        $proServices = $pro->serviceCategories()->pluck('categories.id');
        $wanted      = $event->categories->pluck('id');
        $shared      = $wanted->intersect($proServices);

        $gates = [
            [
                'Not finished or cancelled',
                ! in_array($event->status, ['completed', 'cancelled'], true),
                "status is {$event->status}",
                null,
            ],
            [
                'Published',
                (bool) $event->is_published,
                $event->is_published ? 'is_published is on' : 'is_published is off, so it is a draft',
                'Publishing it is what puts it on the board.',
            ],
            [
                'Still taking proposals',
                RequestLifecycle::acceptsProposals($event),
                $this->lifecycleReason($event, $status),
                $this->lifecycleAdvice($event, $status),
            ],
            [
                'Same state as the pro',
                $this->statePasses($event, $pro),
                $this->stateReason($event, $pro),
                'R38 hides other states: they could not take the work anyway.',
            ],
            [
                'A service the pro offers',
                $shared->isNotEmpty() || $wanted->isEmpty(),
                $this->serviceReason($wanted, $proServices, $shared),
                $proServices->isEmpty() ? 'Run professional:offer-everything to fill them in.' : null,
            ],
        ];

        if (RadiusMatching::enabled() && RadiusMatching::originIsMatchable($pro)) {
            $kept = RadiusMatching::filterEventsForProfessional(collect([$event]), $pro);

            $gates[] = [
                'Within their radius',
                $kept->isNotEmpty(),
                $kept->isNotEmpty() ? 'inside the distance they serve' : 'outside the distance they serve',
                null,
            ];
        }

        return $gates;
    }

    private function lifecycleReason(Event $event, string $status): string
    {
        if (RequestLifecycle::acceptsProposals($event)) {
            return "lifecycle says {$status}";
        }

        return match (true) {
            RequestLifecycle::inExclusiveNegotiation($event)
                => 'the client already moved a bid forward',
            $status === RequestLifecycle::DATE_PASSED
                => 'the event date has passed, so nobody can bid',
            $status === RequestLifecycle::EXPIRED
                => 'the proposal deadline has passed',
            $status === RequestLifecycle::AWARDED
                => 'it has already been awarded',
            default => "lifecycle says {$status}",
        };
    }

    private function lifecycleAdvice(Event $event, string $status): ?string
    {
        return match (true) {
            RequestLifecycle::inExclusiveNegotiation($event)
                => 'It is off the market until that falls through.',
            $status === RequestLifecycle::DATE_PASSED
                => 'Post one with a future date.',
            default => null,
        };
    }

    private function statePasses(Event $event, User $pro): bool
    {
        if (! StateMatching::appliesTo($pro)) {
            return true;
        }

        return Event::whereKey($event->id)
            ->tap(fn ($q) => StateMatching::scopeForViewer($q, $pro))
            ->exists();
    }

    private function stateReason(Event $event, User $pro): string
    {
        if (! StateMatching::appliesTo($pro)) {
            return 'state matching does not apply to this account';
        }

        $theirs = StateMatching::stateOf($pro) ?? 'none';

        return $this->statePasses($event, $pro)
            ? "both in {$theirs}"
            : "the pro is in {$theirs} and this gig is not";
    }

    private function serviceReason(
        \Illuminate\Support\Collection $wanted,
        \Illuminate\Support\Collection $offered,
        \Illuminate\Support\Collection $shared,
    ): string {
        if ($wanted->isEmpty()) {
            return 'the gig names no service, so it is open to anybody';
        }

        if ($shared->isNotEmpty()) {
            return $shared->count() . ' of ' . $wanted->count() . ' wanted services are on their profile';
        }

        return $offered->isEmpty()
            ? 'the pro offers no services at all'
            : 'none of the services this gig wants are on their profile';
    }
}
