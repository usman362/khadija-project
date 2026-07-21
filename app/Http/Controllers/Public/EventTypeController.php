<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

/**
 * Public "Explore by Event Type" — occasion-first discovery (Weddings, Corporate,
 * Birthdays…). Complements the service-first Categories page and the bundle-first
 * Package Search: same marketplace, three entry points by intent.
 *
 * Occasion data is curated/representative (there is no EventType taxonomy model);
 * each card deep-links into /browse or /packages so the browsing stays real.
 */
class EventTypeController extends Controller
{
    private const IMG = 'https://images.unsplash.com/';

    public function index(): View
    {
        $occasions = $this->occasions();
        $featured  = $this->featured();
        $popular   = $this->popular();
        $more      = $this->more();

        // Real package counts per occasion label (matched by title/category/services).
        $names = collect($occasions)->pluck('name')
            ->merge([$featured['hero']['name']])
            ->merge(collect($featured['tiles'])->pluck('name'))
            ->merge(collect($popular)->pluck('name'))
            ->merge(collect($more)->pluck('name'))
            ->unique();

        $counts = [];
        foreach ($names as $name) {
            $counts[$name] = \App\Support\Occasions::known($name)
                ? \App\Models\Package::active()->forOccasion($name)->count()
                : 0;
        }

        return view('public.event-types', [
            'occasions'  => $occasions,
            'featured'   => $featured,
            'popular'    => $popular,
            'more'       => $more,
            'counts'     => $counts,
            'groups'     => ['All', 'Celebrations', 'Corporate', 'Personal', 'Seasonal', 'Cultural'],
        ]);
    }

    /** Left-rail occasion list (label + emoji + group). */
    private function occasions(): array
    {
        return [
            ['name' => 'Weddings',                 'icon' => '💍', 'group' => 'Celebrations'],
            ['name' => 'Birthday Parties',         'icon' => '🎂', 'group' => 'Celebrations'],
            ['name' => 'Corporate Events',         'icon' => '💼', 'group' => 'Corporate'],
            ['name' => 'Baby Showers',             'icon' => '🍼', 'group' => 'Personal'],
            ['name' => 'Anniversaries',            'icon' => '❤️', 'group' => 'Personal'],
            ['name' => 'Graduations',              'icon' => '🎓', 'group' => 'Personal'],
            ['name' => 'Holiday Parties',          'icon' => '🎄', 'group' => 'Seasonal'],
            ['name' => 'Engagement Parties',       'icon' => '💐', 'group' => 'Celebrations'],
            ['name' => 'Festivals & Fairs',        'icon' => '🎪', 'group' => 'Seasonal'],
            ['name' => 'Memorials & Celebrations', 'icon' => '🕊️', 'group' => 'Cultural'],
            ['name' => 'Community Events',         'icon' => '🤝', 'group' => 'Corporate'],
            ['name' => 'Religious Events',         'icon' => '⛪', 'group' => 'Cultural'],
            ['name' => 'Other Occasions',          'icon' => '✨', 'group' => 'All'],
        ];
    }

    /** The big featured occasion + the tiles beside/under it. */
    private function featured(): array
    {
        return [
            'hero' => ['name' => 'Weddings', 'blurb' => 'From intimate ceremonies to grand celebrations.', 'img' => self::IMG . 'photo-1519741497674-611481863552?w=900&q=75&auto=format&fit=crop'],
            'tiles' => [
                ['name' => 'Corporate Events', 'blurb' => 'Professional gatherings & meetings', 'img' => self::IMG . 'photo-1505373877841-8d25f7d46678?w=500&q=70&auto=format&fit=crop'],
                ['name' => 'Birthday Parties', 'blurb' => 'Celebrate another amazing year',    'img' => self::IMG . 'photo-1530103862676-de8c9debad1d?w=500&q=70&auto=format&fit=crop'],
                ['name' => 'Baby Showers',     'blurb' => 'Welcome the little one',            'img' => self::IMG . 'photo-1519689680058-324335c77eba?w=400&q=70&auto=format&fit=crop'],
                ['name' => 'Anniversaries',    'blurb' => 'Celebrate your special milestones', 'img' => self::IMG . 'photo-1464366400600-7168b8af9bc3?w=400&q=70&auto=format&fit=crop'],
                ['name' => 'Engagement Parties','blurb' => 'Pop the champagne!',               'img' => self::IMG . 'photo-1522673607200-164d1b6ce486?w=400&q=70&auto=format&fit=crop'],
            ],
        ];
    }

    /** "Popular Event Types" grid (service-flavoured, with a from-price + badge). */
    private function popular(): array
    {
        return [
            ['name' => 'Catering Events', 'blurb' => 'Food & drink for any occasion',   'from' => 450, 'badge' => 'POPULAR',  'img' => self::IMG . 'photo-1555244162-803834f70033?w=500&q=70&auto=format&fit=crop'],
            ['name' => 'Floral Design',   'blurb' => 'Bouquets, arches & centerpieces', 'from' => 180, 'badge' => 'FEATURED', 'img' => self::IMG . 'photo-1519378058457-4c29a0a2efac?w=500&q=70&auto=format&fit=crop'],
            ['name' => 'Event Planning',  'blurb' => 'Full-service planning & setup',   'from' => 600, 'badge' => 'POPULAR',  'img' => self::IMG . 'photo-1511578314322-379afb476865?w=500&q=70&auto=format&fit=crop'],
            ['name' => 'Venue & Decor',   'blurb' => 'Spaces styled for your occasion', 'from' => 300, 'badge' => 'HOT',      'img' => self::IMG . 'photo-1519167758481-83f550bb49b3?w=500&q=70&auto=format&fit=crop'],
            ['name' => 'Wedding Styling', 'blurb' => 'Table decor & reception design',  'from' => 350, 'badge' => 'FEATURED', 'img' => self::IMG . 'photo-1478146896981-b80fe463b330?w=500&q=70&auto=format&fit=crop'],
            ['name' => 'Cakes & Desserts','blurb' => 'Custom cakes & dessert tables',   'from' => 120, 'badge' => 'NEW',      'img' => self::IMG . 'photo-1535141192574-5d4897c12636?w=500&q=70&auto=format&fit=crop'],
            ['name' => 'Balloon Styling', 'blurb' => 'Arches, garlands & installs',     'from' => 160, 'badge' => 'NEW',      'img' => self::IMG . 'photo-1530103862676-de8c9debad1d?w=500&q=70&auto=format&fit=crop'],
            ['name' => 'Entertainment',   'blurb' => 'Bands, DJs, performers & more',   'from' => 200, 'badge' => 'HOT',      'img' => self::IMG . 'photo-1470229722913-7c0e2dbbafd3?w=500&q=70&auto=format&fit=crop'],
        ];
    }

    /** "More Occasions to Explore" strip. */
    private function more(): array
    {
        return [
            ['name' => 'Holiday Parties',   'blurb' => 'Festive fun & celebrations',   'img' => self::IMG . 'photo-1543934638-bd2e138430c4?w=400&q=70&auto=format&fit=crop'],
            ['name' => 'Fundraisers',       'blurb' => 'Events for a great cause',      'img' => self::IMG . 'photo-1497206365907-f5e630693df0?w=400&q=70&auto=format&fit=crop'],
            ['name' => 'School Events',     'blurb' => 'From prom to field days',       'img' => self::IMG . 'photo-1427504494785-3a9ca7044f45?w=400&q=70&auto=format&fit=crop'],
            ['name' => 'Retirement Parties','blurb' => 'Celebrate new beginnings',      'img' => self::IMG . 'photo-1556761175-5973dc0f32e7?w=400&q=70&auto=format&fit=crop'],
            ['name' => 'Religious Events',  'blurb' => 'Ceremonies & celebrations',     'img' => self::IMG . 'photo-1438232992991-995b7058bbb3?w=400&q=70&auto=format&fit=crop'],
        ];
    }
}
