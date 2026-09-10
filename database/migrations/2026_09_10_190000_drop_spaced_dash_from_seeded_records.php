<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Seeded text already in the database loses its spaced dash.
 *
 * Ali, 2026-09-10: remove the " — " from the whole project. The code and the
 * seeders were changed, but the demo events, packages, profiles and reviews,
 * and the platform disclaimer, were written into the database long ago and
 * would keep showing it.
 *
 * Nothing a person wrote is touched. Short values (titles) change only where
 * the whole value is exactly what the seeder wrote; long ones only where the
 * exact seeded sentence is still inside. The pairs are literal on purpose, so
 * this migration means the same thing whenever it runs.
 *
 * The disclaimer change is punctuation only; the wording and meaning are
 * unchanged. There is no stored version of policy text to bump.
 */
return new class extends Migration
{
    /** events.title and packages.title, whole value. */
    private const EXACT = [
        'Corporate Gala — Full Production' => 'Corporate Gala: Full Production',
        'Waterfront Wedding — Photo + Video + DJ' => 'Waterfront Wedding: Photo + Video + DJ',
        'Wedding Planner — Full Service' => 'Wedding Planner: Full Service',
        'Conference Catering — 200 Guests' => 'Conference Catering, 200 Guests',
        'Complete Celebration — Photo, DJ & Décor' => 'Complete Celebration: Photo, DJ & Décor',
        'Corporate Conference — Catering & AV' => 'Corporate Conference: Catering & AV',
        'DJ & Live Sound — Reception Party' => 'DJ & Live Sound: Reception Party',
    ];

    /** Exact sentences inside longer text. */
    private const WITHIN = [
        'Seeking a photographer for a 150-guest garden wedding — ceremony, reception, family portraits and candids' => 'Seeking a photographer for a 150-guest garden wedding, ceremony, reception, family portraits and candids',
        'emium photography, cinematic video, and custom floral design — all coordinated on one timeline' => 'emium photography, cinematic video, and custom floral design, all coordinated on one timeline',
        'End-to-end planning from concept to day-of execution — vendor sourcing, budget, timeline and on-site coordination' => 'End-to-end planning from concept to day-of execution, vendor sourcing, budget, timeline and on-site coordination',
        'DJ and MC with a full sound system and dance-floor lighting — playlist built with you' => 'DJ and MC with a full sound system and dance-floor lighting, playlist built with you',
        'Full production for large galas and fundraisers — catering, AV, staging, lighting and event staffing under one' => 'Full production for large galas and fundraisers, catering, AV, staging, lighting and event staffing under one',
        'Absolutely incredible — exceeded every expectation' => 'Absolutely incredible, exceeded every expectation',
        'From concept to last dance — we plan luxury weddings and corporate events end to end' => 'From concept to last dance. We plan luxury weddings and corporate events end to end',
        'We sculpt rooms with light — from intimate receptions to large-scale productions' => 'We sculpt rooms with light, from intimate receptions to large-scale productions',
        'On-location glam for brides and bridal parties — flawless, photo-ready looks' => 'On-location glam for brides and bridal parties, flawless, photo-ready looks',
        'Reported in error — the message was from a colleague, not a stranger' => 'Reported in error: the message was from a colleague, not a stranger',
        'tance is focused on involving AI more helpfully at each step — for example, surfacing' => 'tance is focused on involving AI more helpfully at each step, for example, surfacing',
        // As stored: the seeder's text breaks the line straight after this dash.
        'membership outcomes</strong> —
including, but not limited to' => 'membership outcomes</strong>,
including, but not limited to',
    ];

    private const EXACT_COLUMNS  = [['events', 'title'], ['packages', 'title']];
    private const WITHIN_COLUMNS = [['events', 'description'], ['packages', 'description'], ['user_profiles', 'bio'],
                                    ['reviews', 'comment'], ['policy_pages', 'content']];

    public function up(): void
    {
        $this->apply(self::EXACT, self::WITHIN);
    }

    public function down(): void
    {
        $this->apply(array_flip(self::EXACT), array_flip(self::WITHIN));
    }

    private function apply(array $exact, array $within): void
    {
        foreach (self::EXACT_COLUMNS as [$table, $column]) {
            foreach ($exact as $from => $to) {
                DB::table($table)->where($column, $from)->update([$column => $to]);
            }
        }

        foreach (self::WITHIN_COLUMNS as [$table, $column]) {
            foreach ($within as $from => $to) {
                DB::table($table)->where($column, 'like', '%' . $from . '%')->orderBy('id')
                    ->each(function ($row) use ($table, $column, $from, $to) {
                        DB::table($table)->where('id', $row->id)
                            ->update([$column => str_replace($from, $to, $row->{$column})]);
                    });
            }
        }

        // Form payloads are JSON, and JSON stores the dash as \\u2014, so a
        // text search would never find it. Decoded, changed, encoded back the
        // way the model's array cast writes it.
        DB::table('form_submissions')->orderBy('id')->each(function ($row) use ($within) {
            $data = json_decode((string) $row->payload, true);
            if (! is_array($data)) {
                return;
            }
            $walk = function ($v) use (&$walk, $within) {
                return is_array($v) ? array_map($walk, $v) : (is_string($v) ? strtr($v, $within) : $v);
            };
            $new = $walk($data);
            if ($new !== $data) {
                DB::table('form_submissions')->where('id', $row->id)->update(['payload' => json_encode($new)]);
            }
        });
    }
};
