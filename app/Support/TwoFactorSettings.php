<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;

final class TwoFactorSettings
{
    public const KeyEnabled = 'two_factor_enabled';

    public const KeyEmailEnabled = 'two_factor_email_enabled';

    public const KeyRecoveryCodes = 'two_factor_recovery_codes_enabled';

    private const CACHE_TTL = 30;

    private static function cacheKey(): string
    {
        return (string) config('two-factor.cache_key', 'two_factor_settings');
    }

    /**
     * Retrieve all global two-factor settings.
     *
     * @return array<string, mixed>
     */
    public static function all(): array
    {
        return Cache::remember(
            self::cacheKey(),
            now()->addMinutes(self::CACHE_TTL),
            static fn (): array => Setting::query()
                ->whereIn('key', self::keys())
                ->pluck('value', 'key')
                ->all(),
        );
    }

    /**
     * @return list<string>
     */
    public static function keys(): array
    {
        return [
            self::KeyEnabled,
            self::KeyEmailEnabled,
            self::KeyRecoveryCodes,
        ];
    }

    public static function isGloballyEnabled(): bool
    {
        return filter_var(
            self::all()[self::KeyEnabled] ?? false,
            FILTER_VALIDATE_BOOLEAN,
        );
    }

    public static function isEmailGloballyEnabled(): bool
    {
        return filter_var(
            self::all()[self::KeyEmailEnabled] ?? false,
            FILTER_VALIDATE_BOOLEAN,
        );
    }

    public static function areRecoveryCodesEnabled(): bool
    {
        return filter_var(
            self::all()[self::KeyRecoveryCodes] ?? false,
            FILTER_VALIDATE_BOOLEAN,
        );
    }

    /**
     * Persist global two-factor settings.
     *
     * @param  array<string, mixed>  $values
     */
    public static function save(array $values): void
    {
        foreach (self::keys() as $key) {
            if (! array_key_exists($key, $values)) {
                continue;
            }

            Setting::updateOrCreate(
                ['key' => $key],
                ['value' => self::normalize($values[$key])],
            );
        }

        self::flush();
    }

    /**
     * Invalidate the cached settings.
     */
    public static function flush(): void
    {
        Cache::forget(self::cacheKey());
    }

    /**
     * Booleans are stored as "1"/"0" so the shared settings cache stays
     * comparable across the Filament form, the middleware and the login flow.
     */
    private static function normalize(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        return filter_var($value, FILTER_VALIDATE_BOOLEAN) ? '1' : '0';
    }
}
