<?php

namespace App\Rules;

use App\Domain\Settings\Services\SettingsService;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class Recaptcha implements ValidationRule
{
    /**
     * The form identifier (login, register).
     */
    public function __construct(
        private string $form = 'login',
    ) {}

    /**
     * Run the validation rule.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $settings = app(SettingsService::class);

        // If reCAPTCHA is not enabled for this form, skip validation
        if (! $settings->isRecaptchaEnabledFor($this->form)) {
            return;
        }

        $secretKey = $settings->getRecaptchaSecretKey();

        if (empty($secretKey)) {
            Log::warning('reCAPTCHA secret key is not configured.');
            return;
        }

        if (empty($value)) {
            $fail('Please complete the reCAPTCHA verification.');
            return;
        }

        try {
            $response = Http::asForm()->post('https://www.google.com/recaptcha/api/siteverify', [
                'secret' => $secretKey,
                'response' => $value,
                'remoteip' => request()->ip(),
            ]);

            $body = $response->json();

            if (! ($body['success'] ?? false)) {
                $fail('reCAPTCHA verification failed. Please try again.');
                return;
            }

            // For v3, check score (0.0 - 1.0, higher = more likely human)
            $version = $settings->get('recaptcha.version', 'v2');
            if ($version === 'v3') {
                $score = $body['score'] ?? 0;
                if ($score < 0.5) {
                    $fail('reCAPTCHA verification failed. Suspicious activity detected.');
                }
            }
        } catch (\Exception $e) {
            Log::error('reCAPTCHA verification error: ' . $e->getMessage());
            // Don't block the user if Google's API is down
        }
    }
}
