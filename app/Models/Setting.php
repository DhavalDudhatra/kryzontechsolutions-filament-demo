<?php

namespace App\Models;

use App\Support\SettingsStore;
use App\Traits\ManagesStorage;
use Illuminate\Database\Eloquent\Model;

// Settings model for key-value app configuration, branding, theme, and 2FA.
class Setting extends Model
{
    use ManagesStorage;

    protected $fillable = [
        'key',
        'value',
    ];

    // Get the light logo URL with fallback to the default logo.
    public function lightLogoUrl(): ?string
    {
        $logo = SettingsStore::all()['light_logo'] ?? null;

        return $this->getImageUrl($logo, asset('logo-light.svg'));
    }

    // Get the dark logo URL with fallback to the default dark-theme asset.
    public function darkLogoUrl(): ?string
    {
        $logo = SettingsStore::all()['dark_logo'] ?? null;

        return $this->getImageUrl($logo, asset('logo-dark.svg'));
    }

    // Get the favicon URL with fallback to the default favicon.
    public function faviconUrl(): ?string
    {
        $favicon = SettingsStore::all()['favicon'] ?? null;

        return $this->getImageUrl($favicon, asset('favicon.ico'));
    }
}
