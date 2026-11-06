<?php

namespace Database\Seeders;

use App\Models\Setting;
use App\Support\SettingsStore;
use Illuminate\Database\Seeder;

class SettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $defaults = [
            'app_name' => config('app.name'),
            'contact_email' => '',
            'contact_phone' => '',
            'company_address' => '',
            'primary_color' => '#4f46e5',
            'light_logo' => null,
            'dark_logo' => null,
            'favicon' => null,
            'date_format' => config('app.date_format', 'd M Y'),
            'date_time_format' => config('app.date_time_format', 'd M Y, h:i A'),
            'timezone' => config('app.timezone', 'UTC'),
            'email_verification_enabled' => '0',
        ];

        foreach ($defaults as $key => $value) {
            Setting::query()->firstOrCreate(
                ['key' => $key],
                ['value' => $value],
            );
        }

        foreach ([
            'light_logo' => asset('logo-light.svg'),
            'dark_logo' => asset('logo-dark.svg'),
        ] as $key => $defaultAssetUrl) {
            Setting::query()
                ->where('key', $key)
                ->where('value', $defaultAssetUrl)
                ->update(['value' => null]);
        }

        SettingsStore::clearCache();
    }
}
