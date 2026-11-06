<?php

namespace App\Http\Middleware;

use App\Models\Setting;
use App\Support\SettingsStore;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    public function share(Request $request): array
    {
        $user = $request->user();
        $settings = SettingsStore::all();

        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'auth' => ['user' => $user],
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
            'logo' => [
                'light' => (new Setting)->lightLogoUrl(),
                'dark' => (new Setting)->darkLogoUrl(),
            ],
            'settings' => [
                'primary_color' => $settings['primary_color'] ?? '#4f46e5',
                'default_no_image' => $settings['default_no_image'] ?? asset('images/no_images.jpeg'),
                'favicon' => config('app.favicon', asset('favicon.ico')),
            ],
        ];
    }
}
