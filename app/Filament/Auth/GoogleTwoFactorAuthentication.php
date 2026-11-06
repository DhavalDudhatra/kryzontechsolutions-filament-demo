<?php

declare(strict_types=1);

namespace App\Filament\Auth;

use App\Filament\Auth\Actions\SetUpGoogleTwoFactorAction;
use App\Models\User;
use App\Support\TwoFactorSettings;
use Filament\Auth\MultiFactor\App\Actions\DisableAppAuthenticationAction;
use Filament\Auth\MultiFactor\App\Actions\RegenerateAppAuthenticationRecoveryCodesAction;
use Filament\Auth\MultiFactor\App\AppAuthentication;
use Filament\Auth\MultiFactor\App\Contracts\HasAppAuthenticationRecovery;
use Filament\Facades\Filament;
use Filament\Schemas\Components\Component;
use Illuminate\Contracts\Auth\Authenticatable;

/**
 * Google Authenticator 2FA (QR code + verification code).
 * Global settings control whether 2FA and recovery codes are available.
 */
class GoogleTwoFactorAuthentication extends AppAuthentication
{
    public function getId(): string
    {
        return 'app';
    }

    // 2FA is on only if it is enabled globally AND this user has turned it on.
    public function isEnabled(Authenticatable $user): bool
    {
        return TwoFactorSettings::isGloballyEnabled()
            && $user instanceof User
            && $user->is_2fa_enabled
            && parent::isEnabled($user);
    }

    // Recovery codes only work while 2FA and recovery codes are both switched on.
    public function isRecoverable(): bool
    {
        return TwoFactorSettings::isGloballyEnabled() && TwoFactorSettings::areRecoveryCodesEnabled();
    }

    // Users without recovery codes get an empty list, so login never fails.
    public function getRecoveryCodes(HasAppAuthenticationRecovery $user): array
    {
        return $user->getAppAuthenticationRecoveryCodes() ?? [];
    }

    // The three buttons on the profile page: set up, regenerate codes, disable.
    public function getActions(): array
    {
        return [
            SetUpGoogleTwoFactorAction::make($this)
                ->hidden(fn (): bool => $this->currentUserHasTwoFactor()),

            RegenerateAppAuthenticationRecoveryCodesAction::make($this)
                ->visible(fn (): bool => $this->currentUserHasTwoFactor()
                    && $this->isRecoverable()
                    && $this->canRegenerateRecoveryCodes()),

            DisableAppAuthenticationAction::make($this)
                ->visible(fn (): bool => $this->currentUserHasTwoFactor()),
        ];
    }

    // Hide the whole 2FA section on the profile page while 2FA is off globally.
    public function getManagementSchemaComponents(): array
    {
        if (! TwoFactorSettings::isGloballyEnabled()) {
            return [];
        }

        // Keep only the page components; our own buttons come from getActions().
        return array_values(array_filter(
            parent::getManagementSchemaComponents(),
            fn ($item): bool => $item instanceof Component,
        ));
    }

    // Is 2FA on for the user who is logged in right now? (false for guests)
    private function currentUserHasTwoFactor(): bool
    {
        $user = Filament::auth()->user();

        return $user !== null && $this->isEnabled($user);
    }
}
