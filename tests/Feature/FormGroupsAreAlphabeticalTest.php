<?php

namespace Tests\Feature;

use App\Domain\Forms\FormRegistry;
use Tests\TestCase;

/**
 * Requests & Submissions reads A to Z — the group headings and the forms
 * inside each one.
 *
 * DIR-1 (Khadijah), the same instruction the request chooser follows. Sorted
 * in the registry rather than written in order, so every screen that reads it
 * agrees and a form added to a group later cannot land in the wrong place.
 */
class FormGroupsAreAlphabeticalTest extends TestCase
{
    /** @return array<int, string> */
    private function sorted(array $values): array
    {
        usort($values, 'strcmp');

        return $values;
    }

    public function test_the_group_headings_are_in_order(): void
    {
        foreach ([FormRegistry::CLIENT, FormRegistry::PROFESSIONAL, FormRegistry::INFLUENCER] as $audience) {
            $labels = array_column(FormRegistry::groupsForAudience($audience), 'label');

            $this->assertSame(
                $this->sorted($labels),
                $labels,
                "The groups a {$audience} sees are not in alphabetical order.",
            );
        }
    }

    public function test_the_forms_inside_each_group_are_in_order(): void
    {
        foreach ([FormRegistry::CLIENT, FormRegistry::PROFESSIONAL, FormRegistry::INFLUENCER] as $audience) {
            foreach (FormRegistry::groupsForAudience($audience) as $group) {
                $titles = array_column($group['forms'], 'title');

                $this->assertSame(
                    $this->sorted($titles),
                    $titles,
                    "The forms under “{$group['label']}” are not in alphabetical order for a {$audience}.",
                );
            }
        }
    }

    /** Sorting must not lose a form, or hand back a group that has none. */
    public function test_nothing_is_dropped_by_the_sort(): void
    {
        foreach ([FormRegistry::CLIENT, FormRegistry::PROFESSIONAL, FormRegistry::INFLUENCER] as $audience) {
            $available = FormRegistry::forAudience($audience);
            $shown = [];

            foreach (FormRegistry::groupsForAudience($audience) as $group) {
                $this->assertNotEmpty($group['forms']);
                $shown = array_merge($shown, array_keys($group['forms']));
            }

            $grouped = array_filter(
                array_keys($available),
                fn (string $key) => FormRegistry::groupOf($key) !== null,
            );

            sort($shown);
            $grouped = array_values($grouped);
            sort($grouped);

            $this->assertSame($grouped, $shown, "A form went missing for a {$audience}.");
        }
    }
}
