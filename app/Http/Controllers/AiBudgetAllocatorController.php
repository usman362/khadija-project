<?php

namespace App\Http\Controllers;

use App\Domain\AiFeatures\AiFeatureCode;
use App\Domain\AiFeatures\Services\AiFeatureGate;
use App\Domain\AiFeatures\Services\BudgetAllocatorService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Throwable;

class AiBudgetAllocatorController extends Controller
{
    public function __construct(
        private BudgetAllocatorService $service,
        private AiFeatureGate $gate,
    ) {}

    /**
     * Show the Budget Allocator tool page.
     */
    public function show(Request $request): View
    {
        $status = $this->gate->status($request->user(), AiFeatureCode::BUDGET_ALLOCATOR);

        return view('client.ai-tools.budget-allocator', [
            'status' => $status,
        ]);
    }

    /**
     * Generate a budget allocation via AJAX.
     */
    public function allocate(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'event_type'   => ['required', 'string', 'max:120'],
            'total_budget' => ['required', 'numeric', 'min:1', 'max:99999999'],
            'guest_count'  => ['nullable', 'integer', 'min:1', 'max:100000'],
            'currency'     => ['nullable', 'string', 'size:3'],
            'location'     => ['nullable', 'string', 'max:200'],
            'date'         => ['nullable', 'date'],
            'priorities'   => ['nullable', 'string', 'max:500'],
            'notes'        => ['nullable', 'string', 'max:500'],
        ]);

        try {
            $result = $this->service->allocate($request->user(), $validated);
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }

        // Return updated status along with the result
        $status = $this->gate->status($request->user(), AiFeatureCode::BUDGET_ALLOCATOR);

        return response()->json([
            'success' => true,
            'result'  => $result,
            'status'  => $status,
        ]);
    }
}
