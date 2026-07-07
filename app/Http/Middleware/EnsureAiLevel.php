<?php

namespace App\Http\Middleware;

use App\Domain\AiFeatures\AiAccess;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * GigResource IQ™ — AI action gate.
 *
 * Blocks the AI compute endpoints for users below the Semi level on that tool.
 * The tool key is the second URL segment (ai-tools/{slug}/…), which matches the
 * AiToolCatalog key. Manual-tier professionals (and admin-disabled tools) get a
 * clean 402-style "upgrade" JSON instead of running the AI. Under the launch
 * flag (AI_FEATURES_FREE_FOR_ALL) everyone resolves to Maximum, so nothing is
 * blocked — the gate only bites once real tiers are switched on.
 */
class EnsureAiLevel
{
    public function handle(Request $request, Closure $next): Response
    {
        $slug  = (string) $request->segment(2);
        $level = AiAccess::level($request->user(), $slug);

        if ((AiAccess::ORDER[$level] ?? 0) < AiAccess::ORDER['semi']) {
            return response()->json([
                'success' => false,
                'message' => 'AI actions are included on the Pro-Grow and Elite plans. Upgrade to unlock AI on this tool.',
            ], 403);
        }

        return $next($request);
    }
}
