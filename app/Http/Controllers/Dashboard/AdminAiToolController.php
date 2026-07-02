<?php

namespace App\Http\Controllers\Dashboard;

use App\Domain\AiFeatures\AiToolCatalog;
use App\Http\Controllers\Controller;
use App\Models\AiToolSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AdminAiToolController extends Controller
{
    public function index(Request $request): View
    {
        $disabled = AiToolSetting::query()
            ->where('enabled', false)
            ->pluck('tool_key')
            ->all();

        // Annotate every catalog tool with its current enabled state, then group
        // by audience (client / professional / both) for readability.
        $order = ['client' => 0, 'professional' => 1, 'both' => 2];

        $tools = collect(AiToolCatalog::all())
            ->map(function (array $tool) use ($disabled) {
                $tool['enabled'] = ! in_array($tool['key'], $disabled, true);

                return $tool;
            })
            ->sortBy(fn (array $t) => ($order[$t['audience']] ?? 9).'-'.$t['name'])
            ->groupBy('audience');

        $counts = [
            'total'    => collect(AiToolCatalog::all())->count(),
            'enabled'  => collect(AiToolCatalog::all())->reject(fn ($t) => in_array($t['key'], $disabled, true))->count(),
            'disabled' => count($disabled),
        ];

        return view('dashboard.ai-tools.index', compact('tools', 'counts'));
    }

    public function update(Request $request, string $key): RedirectResponse
    {
        $validKeys = collect(AiToolCatalog::all())->pluck('key')->all();

        if (! in_array($key, $validKeys, true)) {
            throw ValidationException::withMessages(['tool_key' => 'Unknown AI tool.']);
        }

        AiToolSetting::updateOrCreate(
            ['tool_key' => $key],
            ['enabled' => $request->boolean('enabled')]
        );

        $state = $request->boolean('enabled') ? 'enabled' : 'disabled';

        return back()->with('status', "AI tool \"{$key}\" {$state}.");
    }
}
