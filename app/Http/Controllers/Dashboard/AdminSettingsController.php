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

    /**
     * Display the reCAPTCHA settings page.
     */
    public function recaptchaSettings(): View
    {
        $recaptchaSettings = $this->settings->getRecaptchaSettings();

        return view('dashboard.settings.recaptcha', [
            'settings' => $recaptchaSettings,
            'isConfigured' => $this->settings->isRecaptchaEnabled(),
        ]);
    }

    /**
     * Update reCAPTCHA settings.
     */
    public function updateRecaptchaSettings(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'enabled' => 'required|in:0,1',
            'site_key' => 'nullable|string|max:500',
            'secret_key' => 'nullable|string|max:500',
            'version' => 'required|in:v2,v3',
            'enable_login' => 'required|in:0,1',
            'enable_register' => 'required|in:0,1',
        ]);

        $this->settings->saveRecaptchaSettings($validated);

        return back()->with('status', 'reCAPTCHA settings updated successfully.');
    }
}
