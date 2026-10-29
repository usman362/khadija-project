<?php

/*
 * A professional's crew, and what a client may see of them.
 *
 * Sir Peter settled the shape of this on 29 October: named team members, no
 * logins. These are the numbers that go with it, here rather than in code so
 * he can change them without a release.
 */
return [

    /*
     * How long after a job ends a client may still reach the crew member's
     * phone number and photo.
     *
     * Sir Peter, 29 October: "restricting Client access to staff contact
     * information after the booking has been completed, with a proposed
     * 30-day access period unless an active dispute or another legitimate
     * requirement calls for longer."
     *
     * The NAME and ROLE are not withheld when this runs out. They are the
     * record of who actually did the work, and a client who cannot say who
     * attended their own wedding has lost something the restriction was never
     * meant to take. It is the contact details that stop being needed once
     * the job is over, and those are what go.
     *
     * 0 would mean the day the job ends. A negative number, or no number at
     * all, leaves access open.
     */
    'client_access_days' => (int) env('STAFF_CONTACT_ACCESS_DAYS', 30),

    /*
     * How long before a service a professional who chose "decide later" must
     * have said who is attending.
     *
     * Sir Peter's point 8. Read by App\Domain\PointOfContact.
     */
    'confirm_hours' => (int) env('STAFF_CONFIRM_HOURS', 24),

];
