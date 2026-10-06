<?php

namespace Tests\Feature;

use App\Domain\Requests\ServiceDetails;
use Tests\TestCase;

/**
 * Sir Peter, 6 October: "if the client selects a MSRs when fillinng out the
 * BR, ER, or DR, then each of them should make and have the timeline for each
 * services requested. i didnt see it in the ER and please recheck the DR
 * webpages as well."
 *
 * It was on the Bidding Request only. The other two never asked, and would not
 * have stored an answer if they had.
 *
 * The timeline is drawn in one partial now and the three forms include it, so
 * this holds the thing that would otherwise rot: that all three still do, and
 * that the saving and the checking are shared rather than repeated.
 */
class EveryRequestFormHasTheTimelineTest extends TestCase
{
    /** All three forms draw it, and all three draw the same one. */
    public function test_all_three_request_forms_include_the_timeline(): void
    {
        foreach ([
            'client/bsr/wizard'            => 'Bidding Request',
            'client/esr/create'            => 'Emergency Request',
            'client/direct-offers/create'  => 'Direct Request',
        ] as $view => $name) {
            $this->assertStringContainsString(
                "client._service_timeline",
                file_get_contents(base_path("resources/views/{$view}.blade.php")),
                "The {$name} no longer asks when each service runs.",
            );
        }
    }

    /** And none of them keeps a copy of its own. */
    public function test_the_timeline_is_written_once(): void
    {
        $partial = base_path('resources/views/client/_service_timeline.blade.php');

        $this->assertFileExists($partial);

        foreach ([
            'client/bsr/wizard',
            'client/esr/create',
            'client/direct-offers/create',
        ] as $view) {
            $this->assertStringNotContainsString(
                'Service schedule / Timeline (per service)',
                file_get_contents(base_path("resources/views/{$view}.blade.php")),
                "{$view} has its own copy of the timeline again.",
            );
        }
    }

    /** The hours are checked the same way wherever they are entered. */
    public function test_the_rules_are_shared(): void
    {
        $rules = ServiceDetails::rules();

        $this->assertArrayHasKey('service_times', $rules);
        $this->assertArrayHasKey('service_times.*.start', $rules);
        $this->assertArrayHasKey('service_times.*.note', $rules);
        $this->assertContains('max:150', $rules['service_times.*.note']);
    }

    /** And stored by the one path all three forms save through. */
    public function test_saving_carries_the_hours(): void
    {
        $this->assertStringContainsString(
            'ServiceTimeline::fromInput',
            file_get_contents(base_path('app/Domain/Requests/ServiceDetails.php')),
            'apply() no longer carries the hours, so two of the three forms would drop them.',
        );
    }
}
