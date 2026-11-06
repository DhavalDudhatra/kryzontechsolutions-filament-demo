<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use App\Models\User;
use Filament\Facades\Filament;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Auth\SessionGuard;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }

    protected function afterSave(): void
    {
        // Re-sync the session's password hash so AuthenticateSession doesn't log out the admin saving their own user.
        $user = $this->getRecord();
        $guard = Filament::auth();
        $authUser = $guard->user();

        if (! $user instanceof User || ! $authUser || ! $guard instanceof SessionGuard) {
            return;
        }

        if ($authUser->is($user) && request()->hasSession()) {
            request()->session()->put(
                'password_hash_'.$guard->getName(),
                $guard->hashPasswordForCookie($user->getAuthPassword()),
            );
        }
    }
}
