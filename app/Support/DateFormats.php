<?php

declare(strict_types=1);

namespace App\Support;

final class DateFormats
{
    // Date format managed in Settings, with the configured fallback.
    public static function date(): string
    {
        return (string) SettingsStore::get('date_format', (string) config('app.date_format', 'd M Y'));
    }

    // Date and time format managed in Settings, with the configured fallback.
    public static function dateTime(): string
    {
        return (string) SettingsStore::get('date_time_format', (string) config('app.date_time_format', 'd M Y, h:i A'));
    }
}
