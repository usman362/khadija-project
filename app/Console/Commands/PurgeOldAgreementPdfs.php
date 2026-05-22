<?php

namespace App\Console\Commands;

use App\Domain\Agreements\Services\AgreementPdfService;
use Illuminate\Console\Command;

/**
 * Daily cleanup — purges cached agreement PDFs older than the
 * retention window (60 days per milestone 3 spec).
 *
 * Scheduled in routes/console.php to run daily at 03:20 local time
 * (shortly after the account-purge job at 03:10).
 */
class PurgeOldAgreementPdfs extends Command
{
    protected $signature = 'agreements:purge-old-pdfs';
    protected $description = 'Delete cached agreement PDFs older than the retention window (60 days).';

    public function handle(AgreementPdfService $pdf): int
    {
        $deleted = $pdf->purgeOldFiles();

        $this->info(sprintf(
            'Purged %d agreement PDF%s older than %d days.',
            $deleted,
            $deleted === 1 ? '' : 's',
            AgreementPdfService::RETENTION_DAYS
        ));

        return self::SUCCESS;
    }
}
