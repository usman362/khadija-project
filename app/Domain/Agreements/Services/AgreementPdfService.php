<?php

namespace App\Domain\Agreements\Services;

use App\Models\Agreement;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Renders a fully-accepted Agreement into a downloadable PDF contract.
 *
 * Per milestone 3 spec:
 *   • Timestamped (generated date + accepted dates)
 *   • Includes both party names (client + supplier)
 *   • Stored for 60 days, then auto-purged via the scheduled cleanup
 *
 * The PDF is cached on the 'private' disk so subsequent downloads are
 * instant. Cache key includes the agreement id + version so editing a
 * fully-accepted agreement invalidates the cached file.
 */
class AgreementPdfService
{
    public const RETENTION_DAYS = 60;
    public const DISK = 'local'; // private disk — never publicly served

    /**
     * Generate (or fetch the cached) PDF binary for the agreement.
     *
     * @return string Raw PDF bytes.
     */
    public function render(Agreement $agreement): string
    {
        $path = $this->pathFor($agreement);

        // Return cached copy if still present (within 60-day retention)
        if (Storage::disk(self::DISK)->exists($path)) {
            return Storage::disk(self::DISK)->get($path);
        }

        // Eager-load the relationships the Blade template uses so we
        // don't lazy-load inside the renderer (slower + risk of N+1).
        $agreement->loadMissing([
            'booking.client:id,name,email',
            'booking.supplier:id,name,email',
            'booking.event:id,title,event_date,location',
            'generator:id,name',
        ]);

        $pdf = Pdf::loadView('agreements.pdf', [
            'agreement' => $agreement,
            'client'    => $agreement->booking?->client,
            'supplier'  => $agreement->booking?->supplier,
            'event'     => $agreement->booking?->event,
            'generatedAt' => now(),
        ])->setPaper('a4');

        $bytes = $pdf->output();
        Storage::disk(self::DISK)->put($path, $bytes);

        return $bytes;
    }

    /**
     * Stream the PDF as a download response with a clean filename.
     */
    public function download(Agreement $agreement): StreamedResponse
    {
        $bytes = $this->render($agreement);
        $filename = $this->filenameFor($agreement);

        return response()->streamDownload(
            fn () => print($bytes),
            $filename,
            [
                'Content-Type'        => 'application/pdf',
                'Content-Length'      => strlen($bytes),
                'Cache-Control'       => 'private, max-age=0, must-revalidate',
                'X-Content-Type-Options' => 'nosniff',
            ]
        );
    }

    /**
     * Purge PDFs older than the retention window. Called from the
     * scheduled cleanup command (kernel.php).
     *
     * @return int Number of files deleted.
     */
    public function purgeOldFiles(): int
    {
        $deleted = 0;
        $cutoff  = now()->subDays(self::RETENTION_DAYS)->timestamp;
        $files   = Storage::disk(self::DISK)->files('agreements');

        foreach ($files as $file) {
            if (Storage::disk(self::DISK)->lastModified($file) < $cutoff) {
                Storage::disk(self::DISK)->delete($file);
                $deleted++;
            }
        }

        return $deleted;
    }

    private function pathFor(Agreement $agreement): string
    {
        // Versioned key — re-generating an agreement bumps version so
        // the previous cached PDF is naturally orphaned (and cleaned up
        // by purgeOldFiles after 60 days).
        return sprintf(
            'agreements/agreement-%d-v%d.pdf',
            $agreement->id,
            $agreement->version ?? 1
        );
    }

    private function filenameFor(Agreement $agreement): string
    {
        $clientName   = $this->slugifyName($agreement->booking?->client?->name ?? 'client');
        $supplierName = $this->slugifyName($agreement->booking?->supplier?->name ?? 'supplier');
        $date         = ($agreement->client_accepted_at ?? $agreement->updated_at)->format('Y-m-d');

        return sprintf(
            'Agreement-%s-%s-%s.pdf',
            $clientName,
            $supplierName,
            $date
        );
    }

    private function slugifyName(string $name): string
    {
        return preg_replace('/[^A-Za-z0-9-]+/', '', str_replace(' ', '-', trim($name))) ?: 'party';
    }
}
