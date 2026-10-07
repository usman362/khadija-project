<?php

use App\Support\DisplayTimezone;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Issue #115, the half that lives in the data.
 *
 * Until now a client typed "5:00 PM" and the platform wrote 17:00 into a
 * column it calls UTC, then read it back and printed 17:00. Two mistakes
 * cancelling out: the time was wrong as an instant and right on the screen.
 *
 * Now that the screen converts, those rows would read four or five hours
 * early — so they have to be re-read as what they always meant. 17:00 in the
 * client's own zone, stored as the instant that actually is.
 *
 * Only the fields somebody typed. created_at, updated_at and every other
 * stamp the application wrote came from now() and have always been true UTC;
 * touching those would break the one set of times that was already right.
 *
 * The zone is the event's own client, not a platform-wide assumption, and
 * the offset is taken at each row's own date so a summer booking and a
 * winter one do not move by the same amount.
 */
return new class extends Migration
{
    /** table => [owner column, [typed datetime columns]] */
    private const TYPED = [
        'events'        => ['client_id', ['starts_at', 'ends_at', 'proposal_deadline']],
        'finalizations' => ['client_id', ['service_start', 'service_end']],
    ];

    public function up(): void
    {
        $this->shift(fn (string $value, string $zone) => Carbon::parse($value, $zone)
            ->setTimezone('UTC')->format('Y-m-d H:i:s'));
    }

    public function down(): void
    {
        $this->shift(fn (string $value, string $zone) => Carbon::parse($value, 'UTC')
            ->setTimezone($zone)->format('Y-m-d H:i:s'));
    }

    private function shift(callable $move): void
    {
        $zones = [];

        foreach (self::TYPED as $table => [$owner, $columns]) {
            if (! \Illuminate\Support\Facades\Schema::hasTable($table)) {
                continue;
            }

            $columns = array_values(array_filter(
                $columns,
                fn ($c) => \Illuminate\Support\Facades\Schema::hasColumn($table, $c)
            ));

            if (empty($columns)) {
                continue;
            }

            DB::table($table)->orderBy('id')->chunkById(200, function ($rows) use ($table, $owner, $columns, $move, &$zones) {
                foreach ($rows as $row) {
                    $ownerId = $row->{$owner} ?? null;

                    if ($ownerId && ! array_key_exists($ownerId, $zones)) {
                        // Resolved from the account the row belongs to, once each.
                        $user = \App\Models\User::with('profile')->find($ownerId);
                        $zones[$ownerId] = $user ? DisplayTimezone::forUser($user) : DisplayTimezone::platformDefault();
                    }

                    $zone = $zones[$ownerId] ?? DisplayTimezone::platformDefault();

                    $changes = [];
                    foreach ($columns as $column) {
                        $value = $row->{$column} ?? null;
                        if (blank($value)) {
                            continue;
                        }
                        $changes[$column] = $move((string) $value, $zone);
                    }

                    if ($changes) {
                        DB::table($table)->where('id', $row->id)->update($changes);
                    }
                }
            });
        }
    }
};
