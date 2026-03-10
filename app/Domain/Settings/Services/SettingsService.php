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
