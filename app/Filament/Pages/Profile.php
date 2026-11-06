<?php

namespace App\Filament\Pages;

use App\Filament\Support\FileUploadConfig;
use App\Models\User;
use App\Support\TwoFactorSettings;
use Filament\Auth\MultiFactor\Contracts\MultiFactorAuthenticationProvider;
use Filament\Auth\Pages\EditProfile as BaseEditProfile;
use Filament\Facades\Filament;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Auth\SessionGuard;

/**
 * Admin profile page: name, email, password, profile photo and (optional) two-factor auth.
 */
class Profile extends BaseEditProfile
{
    protected static ?string $title = 'My Profile';

    protected static ?string $slug = 'profile';

    protected static bool $shouldRegisterNavigation = false;

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Profile information')
                ->description('Manage your name, email address, and profile photo.')
                ->icon(Heroicon::UserCircle)
                ->columns(['default' => 1, 'lg' => 3])
                ->columnSpanFull()
                ->schema([
                    $this->avatarField(),
                    Group::make([
                        TextInput::make('name')
                            ->label('Name')
                            ->required()
                            ->maxLength(50)
                            ->autofocus(),
                        TextInput::make('email')
                            ->label('Email')
                            ->email()
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true)
                            ->live(debounce: 500),
                        $this->getPasswordFormComponent(),
                        $this->getPasswordConfirmationFormComponent(),
                        $this->getCurrentPasswordFormComponent()->columnSpanFull(),
                    ])
                        ->columns(1)
                        ->columnSpan(['default' => 1, 'lg' => 2]),
                ]),
        ]);
    }

    // The profile photo upload. It saves right after the file is chosen.
    private function avatarField(): Component
    {
        /** @var User $user */
        $user = $this->getUser();

        return FileUploadConfig::avatar('avatar', 'Profile Photo', 'avatars', 2048)
            ->formatStateUsing(fn (?string $state): ?string => $state ?: $user->avatar_url)
            ->getUploadedFileNameForStorageUsing(
                fn ($file): string => $user->getKey().'-'.now()->timestamp.'.'.$file->getClientOriginalExtension()
            )
            ->afterStateUpdated(fn (?string $state) => $this->saveAvatar($user, $state))
            ->dehydrated(false)
            ->columnSpan(1);
    }

    // Save the new photo path, delete the old photo file, and keep the login session valid.
    private function saveAvatar(User $user, ?string $newAvatar): void
    {
        if (blank($newAvatar)) {
            return;
        }

        $oldAvatar = $user->avatar_url;
        $user->update(['avatar_url' => $newAvatar]);

        if (filled($oldAvatar) && $oldAvatar !== $newAvatar) {
            $user->deleteStorageFile($oldAvatar);
        }

        // Keep the session's password hash in sync so AuthenticateSession stays valid.
        $guard = Filament::auth();

        if (request()->hasSession() && $guard instanceof SessionGuard) {
            request()->session()->put(
                'password_hash_'.$guard->getName(),
                $guard->hashPasswordForCookie($user->fresh()->getAuthPassword()),
            );
        }
    }

    // Two-factor section: hidden unless 2FA is available and enabled globally.
    public function getMultiFactorAuthenticationContentComponent(): ?Component
    {
        if (
            ! Filament::hasMultiFactorAuthentication()
            || (! TwoFactorSettings::isGloballyEnabled() && ! TwoFactorSettings::isEmailGloballyEnabled())
        ) {
            return null;
        }

        $user = Filament::auth()->user();

        return Section::make()
            ->heading('Two-factor authentication (2FA)')
            ->icon(Heroicon::ShieldCheck)
            ->schema(collect(Filament::getMultiFactorAuthenticationProviders())
                ->sort(fn (MultiFactorAuthenticationProvider $provider): int => $provider->isEnabled($user) ? 0 : 1)
                ->map(fn (MultiFactorAuthenticationProvider $provider): Component => Group::make($provider->getManagementSchemaComponents())
                    ->statePath($provider->getId()))
                ->all());
    }
}
