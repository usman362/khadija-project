<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;

class Setting extends Model
{
    protected $fillable = [
        'key',
        'value',
        'group',
        'type',
        'description',
    ];

    // ── Scopes ─────────────────────────────────────────────

    public function scopeByGroup($query, string $group)
    {
        return $query->where('group', $group);
    }

    public function scopeByKey($query, string $key)
    {
        return $query->where('key', $key);
    }

    // ── Static Helpers ─────────────────────────────────────

    /**
     * Get a setting value by key with caching.
     */
    public static function get(string $key, $default = null): mixed
    {
        return Cache::remember("settings.{$key}", 3600, function () use ($key, $default) {
            $setting = static::byKey($key)->first();

            if (! $setting || $setting->value === null) {
                return $default;
            }

            return $setting->resolveValue();
        });
    }

    /**
     * Set a setting value.
     */
    public static function set(string $key, $value, ?string $group = null, ?string $type = null): void
    {
        $setting = static::byKey($key)->first();

        if ($setting) {
            $data = ['value' => $type === 'encrypted' || $setting->type === 'encrypted'
                ? ($value ? Crypt::encryptString($value) : null)
                : $value,
            ];

            if ($group !== null) {
                $data['group'] = $group;
            }
            if ($type !== null) {
                $data['type'] = $type;
            }

            $setting->update($data);
        } else {
            $storeValue = $type === 'encrypted' && $value
                ? Crypt::encryptString($value)
                : $value;

            static::create([
                'key' => $key,
                'value' => $storeValue,
                'group' => $group ?? 'general',
                'type' => $type ?? 'string',
            ]);
        }

        Cache::forget("settings.{$key}");
    }

    /**
     * Get all settings in a group.
     */
    public static function getGroup(string $group): array
    {
        $settings = static::byGroup($group)->get();
        $result = [];

        foreach ($settings as $setting) {
            $result[$setting->key] = $setting->resolveValue();
        }

        return $result;
    }

    /**
     * Clear all settings cache.
     */
    public static function clearCache(): void
    {
        $keys = static::pluck('key');
        foreach ($keys as $key) {
            Cache::forget("settings.{$key}");
        }
    }

    // ── Value Resolution ───────────────────────────────────

    /**
     * Resolve the stored value based on its type.
     */
    public function resolveValue(): mixed
    {
        if ($this->value === null) {
            return null;
        }

        return match ($this->type) {
            'encrypted' => $this->decryptValue(),
            'boolean' => filter_var($this->value, FILTER_VALIDATE_BOOLEAN),
            'integer' => (int) $this->value,
            default => $this->value,
        };
    }

    /**
     * Decrypt an encrypted value safely.
     */
    private function decryptValue(): ?string
    {
        if (empty($this->value)) {
            return null;
        }

        try {
            return Crypt::decryptString($this->value);
        } catch (\Exception) {
            return null;
        }
    }
}
