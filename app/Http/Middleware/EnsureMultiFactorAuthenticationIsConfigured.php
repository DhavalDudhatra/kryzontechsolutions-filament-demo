<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\User;
use App\Support\TwoFactorSettings;
use Closure;
use Filament\Auth\MultiFactor\MultiFactorChallenge;
use Filament\Facades\Filament;
use Illuminate\Http\Request;

/**
 * Enforces the global two-factor policy for the Filament panel.
 */
class EnsureMultiFactorAuthenticationIsConfigured
{
    public function handle(Request $request, Closure $next): mixed
    {
        $user = Filament::auth()->user();
        if (! $user instanceof User) {
            return $next($request);
        }

        $globalEnabled = TwoFactorSettings::isGloballyEnabled();
        if (! $globalEnabled) {
            return $next($request);
        }

        $hasEnabledProviders = MultiFactorChallenge::make()->hasEnabledProviders($user);
        if ($hasEnabledProviders) {
            return $next($request);
        }

        $mustEnroll = $user->mustEnrollInTwoFactor();
        if ($mustEnroll) {
            return redirect()->guest(Filament::getSetUpRequiredMultiFactorAuthenticationUrl());
        }

        return $next($request);
    }
}
