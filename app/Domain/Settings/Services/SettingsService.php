<?php

namespace App\Domain\Settings\Services;

use App\Models\Setting;

class SettingsService
{
    /**
     * Get a setting value by key.
     */
    public function get(string $key, $default = null): mixed
    {
        return Setting::get($key, $default);
    }

    /**
     * Set a setting value.
     */
    public function set(string $key, $value, ?string $group = null, ?string $type = null): void
    {
        Setting::set($key, $value, $group, $type);
    }

    /**
     * Get all settings in a group.
     */
    public function getGroup(string $group): array
    {
        return Setting::getGroup($group);
    }

    /**
     * Clear settings cache.
     */
    public function clearCache(): void
    {
        Setting::clearCache();
    }

    /**
     * Get all payment settings as a structured array.
     */
    public function getPaymentSettings(): array
    {
        return [
            'active_gateway' => $this->get('payment.active_gateway', 'stripe'),
            'mode' => $this->get('payment.mode', 'test'),
            'currency' => $this->get('payment.currency', 'USD'),
            'stripe_public_key' => $this->get('payment.stripe_public_key'),
            'stripe_secret_key' => $this->get('payment.stripe_secret_key'),
            'stripe_webhook_secret' => $this->get('payment.stripe_webhook_secret'),
            'paypal_client_id' => $this->get('payment.paypal_client_id'),
            'paypal_secret' => $this->get('payment.paypal_secret'),
            'paypal_webhook_id' => $this->get('payment.paypal_webhook_id'),
        ];
    }

    /**
     * Save payment settings from the admin form.
     */
    public function savePaymentSettings(array $data): void
    {
        $mappings = [
            'active_gateway' => ['group' => 'payment', 'type' => 'string'],
            'mode' => ['group' => 'payment', 'type' => 'string'],
            'currency' => ['group' => 'payment', 'type' => 'string'],
            'stripe_public_key' => ['group' => 'payment', 'type' => 'encrypted'],
            'stripe_secret_key' => ['group' => 'payment', 'type' => 'encrypted'],
            'stripe_webhook_secret' => ['group' => 'payment', 'type' => 'encrypted'],
            'paypal_client_id' => ['group' => 'payment', 'type' => 'encrypted'],
            'paypal_secret' => ['group' => 'payment', 'type' => 'encrypted'],
            'paypal_webhook_id' => ['group' => 'payment', 'type' => 'encrypted'],
        ];

        foreach ($mappings as $field => $meta) {
            if (array_key_exists($field, $data)) {
                $this->set(
                    "payment.{$field}",
                    $data[$field],
                    $meta['group'],
                    $meta['type'],
                );
            }
        }
    }

    /**
     * Get all OpenAI settings as a structured array.
     */
    public function getOpenAISettings(): array
    {
        return [
            'api_key' => $this->get('openai.api_key'),
            'model' => $this->get('openai.model', 'gpt-4o-mini'),
            'max_tokens' => $this->get('openai.max_tokens', '4000'),
            'temperature' => $this->get('openai.temperature', '0.3'),
        ];
    }

    /**
     * Save OpenAI settings from the admin form.
     */
    public function saveOpenAISettings(array $data): void
    {
        $mappings = [
            'api_key' => ['group' => 'openai', 'type' => 'encrypted'],
            'model' => ['group' => 'openai', 'type' => 'string'],
            'max_tokens' => ['group' => 'openai', 'type' => 'string'],
            'temperature' => ['group' => 'openai', 'type' => 'string'],
        ];

        foreach ($mappings as $field => $meta) {
            if (array_key_exists($field, $data)) {
                $this->set(
                    "openai.{$field}",
                    $data[$field],
                    $meta['group'],
                    $meta['type'],
                );
            }
        }
    }

    /**
     * Check if OpenAI is configured (DB setting or .env fallback).
     */
    public function isOpenAIConfigured(): bool
    {
        return ! empty($this->get('openai.api_key')) || ! empty(config('services.openai.key'));
    }

    /**
     * Get the resolved OpenAI API key (DB first, then .env fallback).
     */
    public function getOpenAIKey(): ?string
    {
        return $this->get('openai.api_key') ?: config('services.openai.key');
    }

    /**
     * Get the resolved OpenAI model (DB first, then .env fallback).
     */
    public function getOpenAIModel(): string
    {
        return $this->get('openai.model') ?: config('services.openai.model', 'gpt-4o-mini');
    }

    /**
     * Get all reCAPTCHA settings as a structured array.
     */
    public function getRecaptchaSettings(): array
    {
        return [
            'enabled' => $this->get('recaptcha.enabled', '0'),
            'site_key' => $this->get('recaptcha.site_key'),
            'secret_key' => $this->get('recaptcha.secret_key'),
            'version' => $this->get('recaptcha.version', 'v2'),
            'enable_login' => $this->get('recaptcha.enable_login', '1'),
            'enable_register' => $this->get('recaptcha.enable_register', '1'),
        ];
    }

    /**
     * Save reCAPTCHA settings from the admin form.
     */
    public function saveRecaptchaSettings(array $data): void
    {
        $mappings = [
            'enabled' => ['group' => 'recaptcha', 'type' => 'string'],
            'site_key' => ['group' => 'recaptcha', 'type' => 'encrypted'],
            'secret_key' => ['group' => 'recaptcha', 'type' => 'encrypted'],
            'version' => ['group' => 'recaptcha', 'type' => 'string'],
            'enable_login' => ['group' => 'recaptcha', 'type' => 'string'],
            'enable_register' => ['group' => 'recaptcha', 'type' => 'string'],
        ];

        foreach ($mappings as $field => $meta) {
            if (array_key_exists($field, $data)) {
                $this->set(
                    "recaptcha.{$field}",
                    $data[$field],
                    $meta['group'],
                    $meta['type'],
                );
            }
        }
    }

    /**
     * Check if reCAPTCHA is enabled and configured.
     */
    public function isRecaptchaEnabled(): bool
    {
        return $this->get('recaptcha.enabled', '0') === '1'
            && ! empty($this->get('recaptcha.site_key'))
            && ! empty($this->get('recaptcha.secret_key'));
    }

    /**
     * Check if reCAPTCHA should show on a specific form.
     */
    public function isRecaptchaEnabledFor(string $form): bool
    {
        if (! $this->isRecaptchaEnabled()) {
            return false;
        }

        return $this->get("recaptcha.enable_{$form}", '1') === '1';
    }

    /**
     * Get the reCAPTCHA site key.
     */
    public function getRecaptchaSiteKey(): ?string
    {
        return $this->get('recaptcha.site_key');
    }

    /**
     * Get the reCAPTCHA secret key.
     */
    public function getRecaptchaSecretKey(): ?string
    {
        return $this->get('recaptcha.secret_key');
    }

    /**
     * Check if payment gateway is configured.
     */
    public function isGatewayConfigured(string $gateway): bool
    {
        if ($gateway === 'stripe') {
            return ! empty($this->get('payment.stripe_public_key'))
                && ! empty($this->get('payment.stripe_secret_key'));
        }

        if ($gateway === 'paypal') {
            return ! empty($this->get('payment.paypal_client_id'))
                && ! empty($this->get('payment.paypal_secret'));
        }

        return false;
    }
}
