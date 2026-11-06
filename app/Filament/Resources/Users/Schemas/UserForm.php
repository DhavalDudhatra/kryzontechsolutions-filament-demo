<?php

namespace App\Filament\Resources\Users\Schemas;

use App\Enums\Permission;
use App\Filament\Support\FileUploadConfig;
use App\Models\User;
use App\Support\TwoFactorSettings;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rules\Password;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            // User Information Code
            Section::make('User Information')
                ->description('Manage user details, password, and role.')
                ->icon(Heroicon::OutlinedUser)
                ->schema([
                    TextInput::make('name')->label('Full Name')->required()->maxLength(255),
                    TextInput::make('email')->label('Email Address')->email()->required()->unique(ignoreRecord: true)->maxLength(255),
                    FileUploadConfig::avatar(
                        'avatar_url',
                        'Profile Photo',
                        'avatars',
                        2048,
                    )
                        ->afterStateUpdated(function (?string $state, ?User $record): void {
                            if ($record === null || blank($state) || $record->avatar_url === $state) {
                                return;
                            }

                            if (filled($record->avatar_url)) {
                                $record->deleteStorageFile($record->avatar_url);
                            }
                        }),
                    TextInput::make('password')->label('Password')->password()->revealable()
                        ->autocomplete('new-password')
                        // Enforce the app's standard password strength rule.
                        ->rule(Password::default())
                        ->required(fn (string $operation): bool => $operation === 'create')
                        ->dehydrated(fn (?string $state, string $operation): bool => filled($state))
                        ->dehydrateStateUsing(fn (?string $state): ?string => filled($state) ? $state : null)
                        ->helperText('Leave blank when editing to keep the current password.'),

                    Select::make('roles')->label('User Role')
                        ->relationship(
                            'roles',
                            'name',
                            modifyQueryUsing: fn (Builder $query): Builder => $query
                                ->where('guard_name', config('auth.defaults.guard', 'web')),
                        )
                        ->preload()->searchable()->required(),
                ])
                ->columns(2)
                ->columnSpanFull(),

            // 2Fa authentication Code
            Section::make('Two-factor authentication')
                ->description('Require Google Authenticator for this user.')
                ->icon(Heroicon::OutlinedShieldCheck)
                ->visible(fn (): bool => TwoFactorSettings::isGloballyEnabled()
                    && Gate::allows(Permission::UserTwoFactorManage->value))
                ->schema([
                    Toggle::make('is_2fa_enabled')->label('Require Google Authenticator 2FA')
                        ->helperText('When global 2FA is enabled, this user will be challenged for a one-time code at login.')
                        ->inline(false),
                ])
                ->columns(1)
                ->columnSpanFull(),

        ]);
    }
}
