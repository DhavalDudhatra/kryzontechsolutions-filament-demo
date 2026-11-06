<?php

namespace App\Providers\Filament;

use App\Filament\Auth\EmailTwoFactorAuthentication;
use App\Filament\Auth\GoogleTwoFactorAuthentication;
use App\Filament\Pages\Dashboard;
use App\Filament\Pages\Profile;
use App\Http\Middleware\EnsureMultiFactorAuthenticationIsConfigured;
use App\Models\Setting;
use App\Support\SettingsStore;
use Filament\Enums\ThemeMode;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationGroup;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

// Filament admin panel configuration for dynamic branding, authentication, middleware, colors, navigation, and custom theme styling.
class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        $settings = [];
        $lightLogoUrl = asset('logo-light.svg');
        $darkLogoUrl = asset('logo-dark.svg');
        $faviconUrl = asset('favicon.ico');

        if (! app()->runningInConsole()) {
            $settings = SettingsStore::all();
            $lightLogoUrl = (new Setting)->lightLogoUrl();
            $darkLogoUrl = (new Setting)->darkLogoUrl();
            $faviconUrl = (new Setting)->faviconUrl() ?? $faviconUrl;
        }

        $primaryColor = $settings['primary_color'] ?? '#4f46e5';
        $brandName = $settings['app_name'] ?? config('app.name', 'Admin Panel');
        $emailVerificationEnabled = filter_var($settings['email_verification_enabled'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $themeMode = match ($settings['theme_mode'] ?? 'system') {
            'light' => ThemeMode::Light,
            'dark' => ThemeMode::Dark,
            default => ThemeMode::System,
        };

        $primaryColor = preg_match('/^#[0-9a-fA-F]{6}$/', $primaryColor) === 1 ? $primaryColor : '#4f46e5';

        $panel = $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->brandName($brandName)
            ->brandLogo($lightLogoUrl)
            ->darkModeBrandLogo($darkLogoUrl)
            ->brandLogoHeight('2.5rem')
            ->defaultThemeMode($themeMode)
            ->favicon($faviconUrl)
            ->login()
            ->profile(Profile::class, false)
            ->revealablePasswords(false)
            ->multiFactorAuthentication([
                EmailTwoFactorAuthentication::make()->codeExpiryMinutes(4),
                GoogleTwoFactorAuthentication::make()->brandName($brandName),
            ], isRequired: true)
            ->multiFactorAuthenticationRequiredMiddlewareName(EnsureMultiFactorAuthenticationIsConfigured::class)
            // Always return to the listing page after create/save, like a classic admin panel.
            ->resourceCreatePageRedirect('index')
            ->resourceEditPageRedirect('index')
            // Font: keep Filament default (self-hosted Inter Variable, preloaded). Do not use ->font('Inter') — it loads from external CDN with no preload.

            // Theme colors
            ->colors([
                'primary' => Color::hex($primaryColor),
                'gray' => Color::Slate,
                'success' => Color::Green,
                'warning' => Color::Amber,
                'danger' => Color::Red,
                'info' => Color::Sky,
            ])

           // ->topNavigation() // for display in top navbar menu not display sidebar

            // Layout and appearance
            ->maxContentWidth('full')
            ->sidebarCollapsibleOnDesktop() // for collapse with icon display
            // ->sidebarFullyCollapsibleOnDesktop() // for  fully collapse
            ->collapsedSidebarWidth('5rem')
            ->darkMode()
            ->spa() // faster page changes without full reloads

            // NEW: sidebar groups (resources must use the same group names)
            ->navigationGroups([
                NavigationGroup::make('Shop')
                    ->icon('heroicon-o-shopping-bag'),
                NavigationGroup::make('Users & Access')
                    ->icon('heroicon-o-shield-check'),
                NavigationGroup::make('Settings')
                    ->icon('heroicon-o-cog-6-tooth'),
            ])

            ->globalSearch()
            // Database notifications
            ->databaseNotifications()
            // Discover resources and pages
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([
                Dashboard::class,
            ])

            // Discover dashboard widgets
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            // Middleware
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                PreventRequestForgery::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ])

            // Custom theme
            ->viteTheme('resources/css/filament/admin/theme.css');

        if ($emailVerificationEnabled) {
            $panel->emailVerification();
        }

        return $panel;
    }
}
