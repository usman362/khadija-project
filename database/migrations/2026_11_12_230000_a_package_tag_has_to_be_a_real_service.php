<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * The names packages are tagged with, made real.
 *
 * A package carries a list of service names, and the client's browse filter
 * matches against them. The demo data was written with a vocabulary of its
 * own — "Catering / Food", "Lighting & Tech", "DJ / Entertainment" — none of
 * which exists anywhere in the taxonomy. Eighteen of nineteen packages here
 * carried at least one, so Package Search returned nothing for them whatever
 * a client ticked, which is the page the owner demonstrates.
 *
 * The seeder writes real names now, but it creates by slug and leaves rows
 * that already exist alone, so the rows themselves have to be corrected once.
 *
 * Conservative on purpose: only these twelve labels are touched, and anything
 * else a package is tagged with is left exactly as it is. A name nobody
 * recognises is better left visible than quietly turned into a guess.
 *
 * "Add-On" is dropped rather than mapped. It was never a service; it was a
 * label for the kind of package, and there is nothing in the tree it means.
 */
return new class extends Migration
{
    private const MAP = [
        'Photography'             => 'Wedding Photography',
        'Videography'             => 'Videography & Cinematic Films',
        'Floral Design'           => 'Bridal & Ceremony Florals',
        'Planning / Coordination' => 'Day-Of Coordination',
        'Decor & Design'          => 'Themed Decor Design',
        'Lighting & Tech'         => 'Lighting Design (Event-Wide)',
        'DJ / Entertainment'      => 'Wedding DJs',
        'Catering / Food'         => 'Full-Service Catering',
        'Styling'                 => 'Wardrobe Styling & Fittings',
        'DJ'                      => 'Party & Club DJs',
        'Entertainment'           => 'Entertainment, Performers & Activities',
    ];

    private const DROP = ['Add-On'];

    public function up(): void
    {
        foreach (DB::table('packages')->select('id', 'services')->get() as $row) {
            $services = json_decode((string) $row->services, true);

            if (! is_array($services) || $services === []) {
                continue;
            }

            $fixed = [];

            foreach ($services as $name) {
                $name = trim((string) $name);

                if (in_array($name, self::DROP, true)) {
                    continue;
                }

                $fixed[] = self::MAP[$name] ?? $name;
            }

            $fixed = array_values(array_unique($fixed));

            if ($fixed !== $services) {
                DB::table('packages')->where('id', $row->id)
                    ->update(['services' => json_encode($fixed)]);
            }
        }
    }

    /**
     * Not reversed.
     *
     * The old names were never valid, and putting them back would restore
     * packages that no filter can find. Mapping the other way would also be a
     * guess: several of the old labels map onto one real service, so there is
     * no honest way to tell which row had which.
     */
    public function down(): void
    {
    }
};
