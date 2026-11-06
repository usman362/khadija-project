<?php

namespace App\Http\Controllers\Professional;

use App\Domain\WhoHiredMe;
use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\ProfessionalClientNote;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Who has hired this professional, as Khadijah's 28 October screen shows it:
 * the tabs across the top, a table with the relationship, the money, when
 * they were last in touch and what the client asks for every time, and the
 * rules behind each column printed at the bottom.
 *
 * Everything that can be read off bookings is read off bookings; see
 * App\Domain\WhoHiredMe for which rule puts a client in which group. The rest
 * is what the professional writes themselves, and the client never sees it.
 *
 * Route: GET /professional/clients
 */
class ProfessionalClientsController extends Controller
{
    /** Longer than this with no contact and the screen says so. */
    private const QUIET_DAYS = 90;

    public function index(Request $request): View
    {
        $rows = WhoHiredMe::rows($request->user());

        $tab = in_array($request->query('show'), ['repeat', 'one_time', 'past', 'quiet', 'annual'], true)
            ? (string) $request->query('show')
            : 'all';

        $q = trim((string) $request->query('q', ''));

        $shown = $this->only($rows, $tab)
            ->when($q !== '', fn ($list) => $list->filter(function (array $r) use ($q) {
                $hay = mb_strtolower(implode(' ', array_filter([
                    $r['client']['name'], $r['client']['where'], implode(' ', $r['tags']),
                ])));

                return str_contains($hay, mb_strtolower($q));
            }))
            ->values();

        return view('professional.clients.index', [
            'rows'    => $shown,
            'summary' => WhoHiredMe::summary($rows),
            'show'    => $tab,
            'q'       => $q,
            'tabs'    => [
                'all'      => ['Everyone',      $rows->count()],
                'repeat'   => ['Came back',     $this->only($rows, 'repeat')->count()],
                'one_time' => ['Once so far',   $this->only($rows, 'one_time')->count()],
                'past'     => ['Gone quiet',    $this->only($rows, 'past')->count()],
                'quiet'    => ['Needs a chase', $this->only($rows, 'quiet')->count()],
                'annual'   => ['Books yearly',  $this->only($rows, 'annual')->count()],
            ],
            'quietDays' => self::QUIET_DAYS,
        ]);
    }

    /** Save the tags, the note and what this client needs every time. */
    public function saveNote(Request $request, User $client): RedirectResponse
    {
        $me = $request->user();

        $hired = Booking::where('supplier_id', $me->id)->where('client_id', $client->id)->exists();

        if (! $hired) {
            abort(404);
        }

        $data = $request->validate([
            'tags'         => ['nullable', 'string', 'max:160'],
            'note'         => ['nullable', 'string', 'max:400'],
            'requirements' => ['nullable', 'string', 'max:300'],
        ]);

        // Each field is optional and a form may carry only some of them, so
        // an absent one means "not given" rather than "clear it".
        $data += ['tags' => null, 'note' => null, 'requirements' => null];

        $tags = collect(explode(',', (string) ($data['tags'] ?? '')))
            ->map(fn ($t) => trim($t))
            ->filter()
            ->unique()
            ->take(6)
            ->values()
            ->all();

        ProfessionalClientNote::updateOrCreate(
            ['supplier_id' => $me->id, 'client_id' => $client->id],
            [
                'tags'         => $tags ?: null,
                'note'         => $data['note'] ?: null,
                'requirements' => $data['requirements'] ?: null,
            ],
        );

        return back()->with('status', 'Saved your note on ' . $client->name . '.');
    }

    /** The clients as they are filtered on screen, as a file. */
    public function export(Request $request): StreamedResponse
    {
        $rows = $this->only(
            WhoHiredMe::rows($request->user()),
            (string) $request->query('show', 'all'),
        );

        return response()->streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'wb');

            fputcsv($out, ['Client', 'Where', 'Relationship', 'Bookings', 'Lifetime value',
                           'Yours after commission', 'Last worked', 'Days since contact',
                           'Books yearly', 'Tags', 'What they need', 'Your note']);

            foreach ($rows as $r) {
                fputcsv($out, [
                    $r['client']['name'],
                    $r['client']['where'],
                    $r['relationship'],
                    $r['bookings'],
                    $r['gross'],
                    $r['earned'],
                    $r['last']['at']?->format('Y-m-d'),
                    $r['days_quiet'],
                    $r['annual'] ? 'yes' : 'no',
                    implode(', ', $r['tags']),
                    $r['requirements'],
                    $r['note'],
                ]);
            }

            fclose($out);
        }, 'clients-' . now()->format('Y-m-d') . '.csv', ['Content-Type' => 'text/csv']);
    }

    /** @param  \Illuminate\Support\Collection<int, array>  $rows */
    private function only($rows, string $tab)
    {
        // Parenthesised: a match expression cannot be chained off directly.
        return (match ($tab) {
            'repeat'   => $rows->where('relationship', 'repeat'),
            'one_time' => $rows->where('relationship', 'one_time'),
            'past'     => $rows->where('relationship', 'past'),
            // A chase is owed when nobody has spoken in a long time, and when
            // there has been no contact at all. Never is not recent.
            'quiet'    => $rows->filter(fn (array $r) => $r['days_quiet'] === null
                || $r['days_quiet'] >= self::QUIET_DAYS),
            'annual'   => $rows->where('annual', true),
            default    => $rows,
        })->values();
    }
}
