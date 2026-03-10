<?php

namespace App\Http\Controllers\Dashboard;

use App\Domain\Settings\Services\SettingsService;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminSettingsController extends Controller
{
    public function __construct(
        private SettingsService $settings,
    ) {}

    /**
     * Display the payment settings page.
     */
    public function paymentSettings(): View
    {
        $paymentSettings = $this->settings->getPaymentSettings();

        return view('dashboard.settings.payments', [
            'settings' => $paymentSettings,
        ]);
    }

    /**
     * Update payment settings.
     */
    public function updatePaymentSettings(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'active_gateway' => 'required|in:stripe,paypal',
            'mode' => 'required|in:test,live',
            'currency' => 'required|string|max:3',
            'stripe_public_key' => 'nullable|string|max:500',
            'stripe_secret_key' => 'nullable|string|max:500',
            'stripe_webhook_secret' => 'nullable|string|max:500',
            'paypal_client_id' => 'nullable|string|max:500',
            'paypal_secret' => 'nullable|string|max:500',
            'paypal_webhook_id' => 'nullable|string|max:500',
        ]);

        $this->settings->savePaymentSettings($validated);

        return back()->with('status', 'Payment settings updated successfully.');
    }

    /**
     * Display the OpenAI settings page.
     */
    public function openaiSettings(): View
    {
        $openaiSettings = $this->settings->getOpenAISettings();

        return view('dashboard.settings.openai', [
            'settings' => $openaiSettings,
            'isConfigured' => $this->settings->isOpenAIConfigured(),
            'envFallback' => ! empty(config('services.openai.key')),
        ]);
    }

    /**
     * Update OpenAI settings.
     */
    public function updateOpenAISettings(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'api_key' => 'nullable|string|max:500',
            'model' => 'required|string|max:100',
            'max_tokens' => 'required|integer|min:100|max:16000',
            'temperature' => 'required|numeric|min:0|max:2',
        ]);

        $this->settings->saveOpenAISettings($validated);

        return back()->with('status', 'OpenAI settings updated successfully.');
    }
}
