<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * Sir Peter, 6 October: "please review the clients webpages, tabs, steps...
 * for when you want the users to mandate to input required data and add a
 * asterisk so that the user can not get around or move to the next
 * step/page... I saw some area that was not mandated that should be but
 * missing also the asterisk too."
 *
 * The audit found three faults, and the common one was not the missing
 * asterisk. It was the asterisk that was not true: a field marked compulsory
 * that the server has as nullable, which the client can leave blank and send.
 * An asterisk that means nothing teaches people to ignore the ones that do.
 *
 * What is held here is the rule rather than the list: a field marked required
 * must actually be required.
 */
class AnAsteriskMeansRequiredTest extends TestCase
{
    /**
     * Six fields on Post an Event carried an asterisk and nothing required
     * them. Whether they SHOULD be compulsory is a decision about the product;
     * the mark now says what the platform actually insists on.
     */
    public function test_post_an_event_does_not_mark_optional_fields_compulsory(): void
    {
        $markup = file_get_contents(base_path('resources/views/client/post-event/event-info.blade.php'));

        foreach (['Start Time', 'End Time', 'Venue Address', 'Guest Count', 'Estimated Budget'] as $label) {
            $this->assertStringNotContainsString(
                $label . ' <span class="pe-req">*</span>',
                $markup,
                "{$label} is marked compulsory again, and the server does not require it.",
            );
        }
    }

    /** A field only sometimes needed says when, rather than always starring. */
    public function test_conditional_fields_say_their_condition(): void
    {
        $bsr = file_get_contents(base_path('resources/views/client/bsr/wizard.blade.php'));

        $this->assertStringContainsString('Required once you give a street', $bsr,
            'City is starred again; the server has it as nullable until a street is typed.');
        $this->assertStringContainsString('Give this or a maximum', $bsr,
            'Budget from is starred again; either end of the range will do.');

        $this->assertStringContainsString(
            'Required for a hybrid event',
            file_get_contents(base_path('resources/views/client/virtual-hub/brief.blade.php')),
        );
    }

    /** What the server insists on, the form refuses to send without. */
    public function test_always_required_fields_stop_the_form(): void
    {
        $this->assertStringContainsString('<select name="organization_type" id="bwOrgType" aria-label="Organization type" required>',
            file_get_contents(base_path('resources/views/client/bsr/wizard.blade.php')));

        $this->assertStringContainsString('<textarea name="description" required',
            file_get_contents(base_path('resources/views/client/bsr/wizard.blade.php')));

        $profile = file_get_contents(base_path('resources/views/client/profile/index.blade.php'));
        $this->assertStringContainsString('name="name" required', $profile);
        $this->assertStringContainsString('name="email" required', $profile);
    }

    /**
     * A group of checkboxes cannot carry the browser's required attribute, so
     * the one field that needed it is checked on the way out instead. Without
     * this an Emergency Request could be sent with no service on it and the
     * client only found out from the server.
     */
    public function test_an_emergency_request_needs_a_service_before_it_sends(): void
    {
        $markup = file_get_contents(base_path('resources/views/client/esr/create.blade.php'));

        $this->assertStringContainsString('data-needs-service', $markup);
        $this->assertStringContainsString('Choose the service you need before sending this.', $markup);
    }
}
