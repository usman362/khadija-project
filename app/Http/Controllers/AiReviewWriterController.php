<?php

namespace App\Http\Controllers;

use App\Domain\AiFeatures\AiFeatureCode;
use App\Domain\AiFeatures\Services\AiFeatureGate;
use App\Domain\AiFeatures\Services\ReviewWriterService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Throwable;

class AiReviewWriterController extends Controller
{
    public function __construct(
        private ReviewWriterService $service,
        private AiFeatureGate $gate,
    ) {}

    public function show(Request $request): View
    {
        $status = $this->gate->status($request->user(), AiFeatureCode::REVIEW_WRITER);
        return view('client.ai-tools.review-writer', ['status' => $status]);
    }

    public function compose(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'professional_name' => ['required', 'string', 'max:120'],
            'service_type'      => ['nullable', 'string', 'max:120'],
            'event_type'        => ['nullable', 'string', 'max:120'],
            'rating'            => ['required', 'integer', 'min:1', 'max:5'],
            'tone'              => ['required', 'in:friendly,professional,balanced'],
            'thoughts'          => ['required', 'string', 'min:10', 'max:1000'],
        ]);

        try {
            $result = $this->service->compose($request->user(), $validated);
        } catch (Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }

        return response()->json([
            'success' => true,
            'result'  => $result,
            'status'  => $this->gate->status($request->user(), AiFeatureCode::REVIEW_WRITER),
        ]);
    }
}
