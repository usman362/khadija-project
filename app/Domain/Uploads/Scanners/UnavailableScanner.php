<?php

namespace App\Domain\Uploads\Scanners;

use App\Domain\Uploads\Contracts\MalwareScanner;

/**
 * The scanner bound while no vendor has been chosen.
 *
 * It reports NOT SCANNED. That is the whole of it, and the reason it exists
 * rather than the pipeline simply skipping the stage: a skipped stage leaves
 * no record, and the file ends up indistinguishable from one that passed. If
 * a malicious file is ever traced back here, the row has to say we never
 * looked — not imply we looked and were satisfied.
 *
 * The purposes that hold for review (config/uploads.php) are the ones where
 * that answer is not good enough to release the file on.
 *
 * Replaced by binding UPLOAD_SCANNER to a real implementation once Open
 * Decisions row 39 closes.
 */
final class UnavailableScanner implements MalwareScanner
{
    public function scan(string $disk, string $path): array
    {
        return [
            'status'  => self::NOT_SCANNED,
            'scanner' => 'none',
            'detail'  => 'No malware scanner is configured.',
        ];
    }
}
