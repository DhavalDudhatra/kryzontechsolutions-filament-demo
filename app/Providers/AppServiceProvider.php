<?php

namespace App\Providers;

use App\Models\Setting;
use App\Support\SettingsStore;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Apply database-managed settings to the framework configuration.
     */
    public function boot(): void
    {
        $this->applyRuntimeSettings();
    }

    // Keep framework configuration aligned with the values managed in Settings.
    private function applyRuntimeSettings(): void
    {
        if (app()->runningInConsole()) {
            return;
        }

        $settings = SettingsStore::all();

        if ($settings === []) {
            return;
        }

        if (filled($settings['app_name'] ?? null)) {
            config([
                'app.name' => $settings['app_name'],
                'mail.from.name' => $settings['app_name'],
            ]);
        }

        $timezone = $settings['timezone'] ?? null;

        if (is_string($timezone) && in_array($timezone, timezone_identifiers_list(), true)) {
            config(['app.timezone' => $timezone]);
            date_default_timezone_set($timezone);
        }

        foreach ([
            'date_format' => 'app.date_format',
            'date_time_format' => 'app.date_time_format',
        ] as $setting => $configKey) {
            if (filled($settings[$setting] ?? null)) {
                config([$configKey => $settings[$setting]]);
            }
        }

        config(['app.favicon' => (new Setting)->faviconUrl()]);
    }
}
