<?php

namespace App\Http\Middleware;

use App\Domain\AiFeatures\AiAccess;
use App\Models\AiFeatureUsage;
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
        $user  = $request->user();
        $level = AiAccess::level($user, $slug);

        if ((AiAccess::ORDER[$level] ?? 0) < AiAccess::ORDER['semi']) {
            return response()->json([
                'success' => false,
                'message' => 'AI actions are included on the Pro-Grow and Elite plans. Upgrade to unlock AI on this tool.',
            ], 403);
        }

        // ── GigResource IQ credit metering (pilot) ─────────────────────────
        if (AiAccess::creditsEnabled()) {
            $metered = $user && ! $user->isAdmin();
            $cost    = $metered ? AiAccess::creditCost($slug) : 0;

            if ($metered) {
                $grant = AiAccess::monthlyCreditGrant($user);
                if ($grant < PHP_INT_MAX && ($user->aiCreditsUsedThisMonth() + $cost) > $grant) {
                    return response()->json([
                        'success'   => false,
                        'message'   => "You've used your AI Assist Credits for this month. They reset on the 1st — upgrade your plan for more.",
                        'remaining' => $user->aiCreditsRemaining(),
                    ], 429);
                }
            }

            $response = $next($request);

            if ($metered && $response->getStatusCode() < 400) {
                AiFeatureUsage::create([
                    'user_id'      => $user->id,
                    'feature_code' => AiAccess::BETA_ACTION_PREFIX . $slug,
                    'tokens_used'  => 0,
                    'credits'      => $cost,
                    'metadata'     => null,
                    'created_at'   => now(),
                ]);
            }

            return $response;
        }

        // ── Legacy Phase-4 free-beta action cap (when credits disabled) ────
        $capped = $user && AiAccess::isFreeBetaUser($user) && ($cap = AiAccess::freeBetaCap()) > 0;
        if ($capped && $user->aiFreeBetaUsedThisMonth() >= $cap) {
            return response()->json([
                'success'   => false,
                'message'   => "You've used all {$cap} of your free AI actions for this month. They reset on the 1st.",
                'remaining' => 0,
            ], 429);
        }

        $response = $next($request);

        if ($capped && $response->getStatusCode() < 400) {
            AiFeatureUsage::create([
                'user_id'      => $user->id,
                'feature_code' => AiAccess::BETA_ACTION_PREFIX . $slug,
                'tokens_used'  => 0,
                'credits'      => 1,
                'metadata'     => null,
                'created_at'   => now(),
            ]);
        }

        return $response;
    }
}
