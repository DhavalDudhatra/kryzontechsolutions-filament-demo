<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

final class SettingsStore
{
    // Retrieve application settings
    /** @return array<string, string|null> */
    public static function all(): array
    {
        if (! Schema::hasTable('settings')) {
            return [];
        }

        return Cache::remember('app_settings', 300,
            static fn (): array => Setting::query()->pluck('value', 'key')->all(),
        );
    }

    // Read a single setting, falling back when it is missing or empty
    public static function get(string $key, ?string $default = null): ?string
    {
        $value = self::all()[$key] ?? null;

        return filled($value) ? (string) $value : $default;
    }

    public static function clearCache(): void
    {
        Cache::forget('app_settings');
    }
}
