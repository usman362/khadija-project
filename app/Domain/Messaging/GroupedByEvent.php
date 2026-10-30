<?php

namespace App\Domain\Messaging;

/**
 * The message list, gathered under the event each conversation is about.
 *
 * Sir Peter, 30 October: "the left messaging column seems to always be full
 * of those that relate to the same project". Khadijah agreed and named the
 * fix: "group all conversations relating to the same project under the event
 * title in the left messaging column".
 *
 * A flat list sorted by the last message interleaves five threads about one
 * wedding with three about a corporate dinner, and the person reading it has
 * to rebuild the grouping in their head every time. There was already an
 * event dropdown above the list, but a filter is the wrong shape for this:
 * it shows one event by hiding the rest, and the question is "what is going
 * on with each of my events", not "show me only this one".
 *
 * ORDER. Groups by their newest message, so the event something just
 * happened on is at the top, and rows inside a group the same way. That keeps
 * the list's old behaviour recognisable: the newest thing is still first, it
 * just brings its event with it.
 *
 * Conversations about no event go last, under their own heading. They are
 * real — a direct message between two people is about nobody's wedding — and
 * burying them in an "Other" at the top would put the least urgent thing in
 * the most urgent place.
 */
final class GroupedByEvent
{
    /** What the heading says when a conversation belongs to no event. */
    public const LOOSE = 'Not about an event';

    /**
     * @param  array<int, array<string, mixed>>  $rows  summarised conversations
     * @return array<int, array{id: ?int, title: string, rows: array<int, array<string, mixed>>}>
     */
    public static function of(array $rows): array
    {
        $groups = [];

        foreach ($rows as $row) {
            $id    = $row['event']['id'] ?? null;
            $key   = $id === null ? '' : (string) $id;
            $title = $row['event']['title'] ?? self::LOOSE;

            $groups[$key] ??= ['id' => $id, 'title' => $title, 'rows' => [], 'at' => 0];
            $groups[$key]['rows'][] = $row;
            // The group is as recent as its most recent conversation.
            $groups[$key]['at'] = max($groups[$key]['at'], (int) ($row['sortAt'] ?? 0));
        }

        foreach ($groups as &$group) {
            usort($group['rows'], fn ($a, $b) => (int) ($b['sortAt'] ?? 0) <=> (int) ($a['sortAt'] ?? 0));
        }
        unset($group);

        uasort($groups, function (array $a, array $b) {
            // Loose conversations last, whatever their dates say.
            if (($a['id'] === null) !== ($b['id'] === null)) {
                return $a['id'] === null ? 1 : -1;
            }

            return $b['at'] <=> $a['at'];
        });

        return array_values(array_map(
            fn (array $g) => ['id' => $g['id'], 'title' => $g['title'], 'rows' => $g['rows']],
            $groups,
        ));
    }
}
