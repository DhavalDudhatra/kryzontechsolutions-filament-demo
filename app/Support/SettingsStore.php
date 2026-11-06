<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

final class SettingsStore
{
    private static ?bool $tableExists = null;

    // Retrieve application settings
    /** @return array<string, string|null> */
    public static function all(): array
    {
        if (! self::tableExists()) {
            return [];
        }

        return Cache::remember('app_settings', 300,
            static fn (): array => Setting::query()->pluck('value', 'key')->all(),
        );
    }

    private static function tableExists(): bool
    {
        return self::$tableExists ??= Cache::remember(
            'settings_table_exists', 3600,
            static fn (): bool => Schema::hasTable('settings'),
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
        Cache::forget('settings_table_exists');
        self::$tableExists = null;
    }
}
