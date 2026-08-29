<?php

namespace App\Http\Controllers\Public;

use App\Domain\Taxonomy\EventHierarchy;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Event Hierarchy Review — Peter's four-level cascade.
 *
 * The workflow diagram sets out three outcomes and this implements exactly
 * those three:
 *
 *   A  a Main Event is chosen  → Level 2 opens, showing only what belongs to
 *                                that event; 3 and 4 stay shut.
 *   B  Level 2 is reached for  → refused, the value is cleared, and the reader
 *      before Level 1             is told why.
 *   —  nothing chosen          → everything shut, and the page says where to
 *                                start.
 *
 * B is enforced HERE, not only by the disabled attribute on the dropdown. A
 * disabled select stops a mouse; it does not stop a URL, and "the system
 * prevents skipping Level 1" has to be true of the system rather than of the
 * cursor.
 */
class EventHierarchyController extends Controller
{
    public function index(): View
    {
        return view('public.event-hierarchy', [
            'levels'  => EventHierarchy::LEVELS,
            'events'  => EventHierarchy::mainEvents(),
            'version' => EventHierarchy::version(),
            'depth'   => EventHierarchy::depth(),
        ]);
    }

    /** The options for one level, given what was chosen above it. */
    public function options(Request $request): JsonResponse
    {
        $level = (int) $request->query('level');
        $parent = $request->query('parent');
        $parent = is_numeric($parent) ? (int) $parent : null;

        if (! array_key_exists($level, EventHierarchy::LEVELS)) {
            return response()->json(['message' => 'No such level.'], 422);
        }

        /*
         * Outcome B, on the server. Asking for level 3 without saying what was
         * chosen at level 2 is the same skip the diagram forbids, and it
         * answers the same way rather than quietly returning everything.
         */
        if ($level > 1 && $parent === null) {
            return response()->json([
                'blocked' => true,
                'message' => 'Please select a Main Event (Level 1) first.',
                'detail'  => 'Level ' . $level . ' options depend on what you choose above it.',
                'options' => [],
            ], 422);
        }

        $options = EventHierarchy::optionsFor($level, $parent);

        return response()->json([
            'blocked' => false,
            'options' => $options->map(fn ($c) => [
                'id'   => $c->id,
                'name' => $c->name,
                // The masterlist's ranking, shown so the reader can see WHY
                // the order is what it is. Level 2 only; nothing else is ranked.
                'tier' => $c->tier ?? null,
            ])->values(),
            // A level with nothing behind it says so. The live tree has no
            // fourth level, and inventing one would break the diagram's own
            // closing rule.
            'empty_reason' => $options->isEmpty()
                ? self::emptyReason($level)
                : null,
        ]);
    }

    private static function emptyReason(int $level): string
    {
        return $level === 4
            ? 'No service specialties are listed for this service in the source.'
            : 'Nothing is listed under this selection in the source.';
    }
}
