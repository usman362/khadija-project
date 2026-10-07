<?php

namespace App\Http\Controllers\Client;

use App\Domain\Requests\BudgetGuide;
use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * What professionals have charged, for the forms that pick their services on
 * the same page as the budget.
 *
 * The Bidding Request is a wizard: by the time it asks about money the
 * services were settled on an earlier step, so the guide is worked out on the
 * server and printed. The Emergency and Direct Requests ask both questions at
 * once, so this answers as the services are ticked.
 *
 * It says nothing the printed version would not say, including why it has
 * nothing to say: the reasoning lives in App\Domain\Requests\BudgetGuide and
 * this only carries it.
 */
class ClientBudgetGuideController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $data = $request->validate([
            'services'   => ['nullable', 'array', 'max:20'],
            'services.*' => ['integer'],
        ]);

        $ids   = array_values(array_unique(array_map('intval', (array) ($data['services'] ?? []))));
        $state = $request->user()?->profile?->state;

        if ($ids === []) {
            return response()->json(['services' => []]);
        }

        $names = Category::whereIn('id', $ids)->pluck('name', 'id');

        $out = [];

        foreach ($ids as $id) {
            if (! isset($names[$id])) {
                continue;
            }

            $guide = BudgetGuide::forService($id, $state);

            $out[] = [
                'id'       => $id,
                'name'     => $names[$id],
                'has'      => (bool) $guide,
                'low'      => $guide['low'] ?? null,
                'high'     => $guide['high'] ?? null,
                'typical'  => $guide['typical'] ?? null,
                // One sentence either way: the figures, or why there are none.
                'sentence' => $guide
                    ? BudgetGuide::sentence($guide)
                    : BudgetGuide::silence($id, $state),
            ];
        }

        return response()->json(['services' => $out]);
    }
}
