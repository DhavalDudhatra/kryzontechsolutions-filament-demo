<?php

declare(strict_types=1);

namespace App\Filament\Auth;

use App\Support\TwoFactorSettings;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Auth\MultiFactor\Email\EmailAuthentication;
use Filament\Schemas\Components\Component;
use Illuminate\Contracts\Auth\Authenticatable;

class EmailTwoFactorAuthentication extends EmailAuthentication
{
    public function isEnabled(Authenticatable $user): bool
    {
        return TwoFactorSettings::isEmailGloballyEnabled()
            && parent::isEnabled($user);
    }

    /**
     * @return array<Component | Action | ActionGroup>
     */
    public function getManagementSchemaComponents(): array
    {
        if (! TwoFactorSettings::isEmailGloballyEnabled()) {
            return [];
        }

        return parent::getManagementSchemaComponents();
    }
}
