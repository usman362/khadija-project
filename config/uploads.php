<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Rule R54 — the one upload path ("CSR-001")
    |--------------------------------------------------------------------------
    |
    | Every file uploaded anywhere on GigResource passes through
    | App\Domain\Uploads\UploadPipeline: quarantine → validate → scan →
    | decide → store → audit. No feature builds its own path, so this file is
    | the only place the answers live.
    |
    */

    /*
    | Where a file waits while it is being decided on. Never the public disk —
    | the whole point of quarantine is that nothing is reachable until the
    | pipeline says so.
    */
    'quarantine_disk' => env('UPLOAD_QUARANTINE_DISK', 'private'),

    /*
    |--------------------------------------------------------------------------
    | Malware scanning
    |--------------------------------------------------------------------------
    |
    | No vendor has been chosen — Open Decisions row 39, "malware-scanning
    | vendor & content-moderation tooling not chosen", awaiting the Owner.
    |
    | Until one is, the bound scanner reports NOT SCANNED. It does not report
    | clean. A file nobody scanned must never be recorded as one that passed,
    | because that record is what a later dispute would be read against.
    |
    */
    'scanner' => env('UPLOAD_SCANNER', \App\Domain\Uploads\Scanners\UnavailableScanner::class),

    /*
    |--------------------------------------------------------------------------
    | Purposes
    |--------------------------------------------------------------------------
    |
    | Every upload names one. A purpose carries its own allowlist, its own
    | ceiling, and its own answer to "what happens when we could not scan it".
    |
    |   holds_for_review  true  → an unscannable file waits for an admin
    |                     false → it is approved, and the audit row still says
    |                             plainly that it was never scanned
    |
    | The split is by who ends up looking at the file. An avatar is seen by
    | the person who chose it and by anyone reading their profile; a licence
    | document is evidence, and an event's photographs are a client's private
    | occasion. Holding everything would stop a professional changing their
    | picture until an admin woke up; holding nothing would make quarantine
    | decorative.
    |
    */
    'purposes' => [

        'avatar' => [
            'label'            => 'Profile picture',
            'disk'             => 'public',
            'extensions'       => ['jpg', 'jpeg', 'png', 'webp'],
            'mimes'            => ['image/jpeg', 'image/png', 'image/webp'],
            'max_kb'           => 5120,
            'holds_for_review' => false,
            'retain_days'      => null,   // lives as long as the account
        ],

        'cover' => [
            'label'            => 'Cover image',
            'disk'             => 'public',
            'extensions'       => ['jpg', 'jpeg', 'png', 'webp'],
            'mimes'            => ['image/jpeg', 'image/png', 'image/webp'],
            'max_kb'           => 8192,
            'holds_for_review' => false,
            'retain_days'      => null,
        ],

        /*
        | Trade licence, certificate of insurance, workers' comp. These were
        | written to the PUBLIC disk, which put a professional's licence
        | document on a URL anyone could reach without signing in. They are
        | evidence, they are personal, and they already go to an admin queue
        | for approval — so they stay private and they hold.
        */
        'verification' => [
            'label'            => 'Verification document',
            'disk'             => 'private',
            'extensions'       => ['jpg', 'jpeg', 'png', 'webp', 'pdf'],
            'mimes'            => ['image/jpeg', 'image/png', 'image/webp', 'application/pdf'],
            'max_kb'           => 5120,
            'holds_for_review' => true,
            'retain_days'      => 2555,   // seven years, matching tax records
        ],

        /*
        | A client's event photographs and documents. R55 governs these: they
        | will contain minors and that is not a reason to refuse them.
        */
        'event_media' => [
            'label'            => 'Event photo or document',
            'disk'             => 'private',
            'extensions'       => ['jpg', 'jpeg', 'png', 'webp', 'pdf', 'mp4', 'webm'],
            'mimes'            => ['image/jpeg', 'image/png', 'image/webp', 'application/pdf', 'video/mp4', 'video/webm'],
            'max_kb'           => 25600,
            'holds_for_review' => true,
            'retain_days'      => 1095,
        ],

        'message_attachment' => [
            'label'            => 'Message attachment',
            'disk'             => 'private',
            'extensions'       => ['jpg', 'jpeg', 'png', 'gif', 'webp', 'pdf', 'doc', 'docx', 'xls', 'xlsx', 'mp4', 'webm'],
            'mimes'            => [
                'image/jpeg', 'image/png', 'image/gif', 'image/webp', 'application/pdf',
                'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'video/mp4', 'video/webm',
            ],
            'max_kb'           => 10240,
            'holds_for_review' => false,
            'retain_days'      => 1095,
        ],

        /*
        | Admin-authored site content — category artwork, blog images, CMS.
        | Public by design and uploaded by staff, so it does not hold.
        */
        'site_content' => [
            'label'            => 'Site content image',
            'disk'             => 'public',
            'extensions'       => ['jpg', 'jpeg', 'png', 'webp', 'svg'],
            'mimes'            => ['image/jpeg', 'image/png', 'image/webp', 'image/svg+xml'],
            'max_kb'           => 8192,
            'holds_for_review' => false,
            'retain_days'      => null,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Rule R55 — images containing minors
    |--------------------------------------------------------------------------
    |
    | Event photographs contain children, and R55 is explicit that this is NOT
    | blanket-prohibited: a wedding, a birthday, a school prize-giving. The
    | rule places responsibility on the uploader for rights and permission,
    | including a parent or guardian's where that applies, and keeps
    | GigResource's power to remove an image regardless of any consent
    | claimed.
    |
    | It is enforced as an ATTESTATION, not a detector. No automated check can
    | tell whether a guardian agreed, and a detector that merely spots a child
    | would flag every genuine event photograph on the platform — turning R55
    | into the blanket prohibition it exists to prevent.
    |
    */
    'minors' => [
        'attestation_required_for' => ['event_media'],
        'attestation' => 'I have the right to upload these images, including permission from a '
            . 'parent or guardian for anyone under 18 who appears in them.',
    ],

];
