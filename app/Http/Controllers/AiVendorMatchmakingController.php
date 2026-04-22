<?php

namespace App\Http\Controllers;

use App\Domain\AiFeatures\AiFeatureCode;
use App\Domain\AiFeatures\Services\AiFeatureGate;
use App\Domain\AiFeatures\Services\VendorMatchmakingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Throwable;

class AiVendorMatchmakingController extends Controller
{
    public function __construct(
        private VendorMatchmakingService $service,
        private AiFeatureGate $gate,
    ) {}

    public function show(Request $request): View
    {
        $status = $this->gate->status($request->user(), AiFeatureCode::VENDOR_MATCHMAKING);
        return view('client.ai-tools.vendor-matchmaking', ['status' => $status]);
    }

    public function match(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'event_type'   => ['required', 'string', 'max:120'],
            'budget'       => ['nullable', 'numeric', 'min:0', 'max:99999999'],
            'guest_count'  => ['nullable', 'integer', 'min:1', 'max:100000'],
            'location'     => ['nullable', 'string', 'max:200'],
            'date'         => ['nullable', 'date'],
            'requirements' => ['nullable', 'string', 'max:1000'],
        ]);

        try {
            $result = $this->service->match($request->user(), $validated);
        } catch (Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }

        return response()->json([
            'success' => true,
            'result'  => $result,
            'status'  => $this->gate->status($request->user(), AiFeatureCode::VENDOR_MATCHMAKING),
        ]);
    }
}
