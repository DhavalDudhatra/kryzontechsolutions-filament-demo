<?php

namespace Database\Seeders;

use App\Models\Setting;
use App\Support\TwoFactorSettings;
use Illuminate\Database\Seeder;

/**
 * Seeds the global two-factor authentication defaults.
 *
 * Defaults are deliberately off so a fresh install never locks
 * administrators out before they choose a policy.
 */
class TwoFactorSettingsSeeder extends Seeder
{
    public function run(): void
    {
        $defaults = [
            TwoFactorSettings::KeyEnabled => '0',
            TwoFactorSettings::KeyEmailEnabled => '0',
            TwoFactorSettings::KeyRecoveryCodes => '1',
        ];

        foreach ($defaults as $key => $value) {
            Setting::query()->firstOrCreate(['key' => $key], ['value' => $value]);
        }

        TwoFactorSettings::flush();
    }
}
