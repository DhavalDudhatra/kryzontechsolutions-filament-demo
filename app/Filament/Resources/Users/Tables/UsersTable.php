<?php

namespace App\Filament\Resources\Users\Tables;

use App\Enums\Permission;
use App\Models\Category;
use App\Models\EmailTemplate;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Services\EmailService;
use App\Support\DateFormats;
use App\Support\TwoFactorSettings;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

class UsersTable
{
    /** Whether the current user may change another user's two-factor state.*/
    private static function canManage(): bool
    {
        return Gate::allows(Permission::UserTwoFactorManage->value);
    }

    /** Email the affected user about a security change on their account. */
    private static function sendSecurityAlert(User $record, string $event): void
    {
        $actor = Auth::user()->name ?? 'an administrator';

        app(EmailService::class)->sendTemplate('security_alert', $record->email, [
            'user' => ['name' => $record->name, 'email' => $record->email],
            'event' => $event.' by '.$actor,
        ]);
    }

    // For User Tables
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('avatar_url')
                    ->label('Avatar')
                    ->disk('public')
                    ->imageSize(42)
                    ->circular()
                    ->defaultImageUrl('data:image/svg+xml,%3Csvg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 42 42"%3E%3Ccircle cx="21" cy="21" r="20" fill="%23f3f4f6"/%3E%3Ccircle cx="21" cy="16" r="6" fill="none" stroke="%236b7280" stroke-width="2.5"/%3E%3Cpath d="M10 34c1.8-6 5.5-9 11-9s9.2 3 11 9" fill="none" stroke="%236b7280" stroke-linecap="round" stroke-width="2.5"/%3E%3C/svg%3E')
                    ->toggleable(),
                TextColumn::make('name')->searchable()->sortable()->toggleable(),
                TextColumn::make('createdBy.name')->label('Created By')->placeholder('-')->sortable()->toggleable(),
                TextColumn::make('email')->label('Email address')->searchable()->sortable()->toggleable(),
                TextColumn::make('roles.name')->label('Role')->badge()->separator(',')->placeholder('No role')->toggleable(),
                TextColumn::make('two_factor_status')->label('Two-factor')->badge()
                    ->visible(fn (): bool => TwoFactorSettings::isGloballyEnabled())
                    ->state(fn (User $record): string => $record->twoFactorStatus())
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'enabled' => 'Enabled',
                        'pending' => 'Pending',
                        default => 'Disabled',
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'enabled' => 'success',
                        'pending' => 'warning',
                        default => 'danger',
                    })
                    ->icon(fn (string $state): string => match ($state) {
                        'enabled' => 'heroicon-o-shield-check',
                        'pending' => 'heroicon-o-exclamation-triangle',
                        default => 'heroicon-o-shield-exclamation',
                    })->toggleable(),
                TextColumn::make('created_at')->dateTime(DateFormats::dateTime())->sortable()->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')->dateTime(DateFormats::dateTime())->sortable()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('roles')
                    ->relationship(
                        'roles',
                        'name',
                        modifyQueryUsing: fn (Builder $query): Builder => $query
                            ->where('guard_name', config('auth.defaults.guard', 'web')),
                    )
                    ->label('Role')
                    // ->multiple()
                    ->preload(),
            ])
            ->recordActions([
                ViewAction::make()->iconButton(),
                EditAction::make()->iconButton(),
                // For disableTwoFactor Button
                Action::make('disableTwoFactor')->label('Disable two-factor')
                    ->iconButton()
                    ->icon(Heroicon::OutlinedShieldExclamation)
                    ->color('danger')
                    ->tooltip('Disable and revoke two-factor authentication')
                    ->visible(fn (User $record): bool => self::canManage()
                        && TwoFactorSettings::isGloballyEnabled()
                        && $record->is_2fa_enabled)
                    ->requiresConfirmation()
                    ->modalHeading('Disable two-factor authentication')
                    ->modalDescription(fn (User $record): string => "This turns off Google Authenticator for {$record->name}, revokes the authenticator secret and all unused recovery codes. They will sign in with their password only.")
                    ->action(function (User $record): void {
                        $record->disableTwoFactorAuthentication();

                        self::sendSecurityAlert($record, 'Two-factor authentication was disabled');

                        Notification::make()
                            ->title('Two-factor authentication disabled')
                            ->body("Two-factor authentication is switched off for {$record->name} and their credentials have been revoked.")
                            ->success()
                            ->send();
                    }),

                // For resetTwoFactor Button
                Action::make('resetTwoFactor')->label('Reset two-factor')
                    ->iconButton()
                    ->icon(Heroicon::OutlinedArrowPath)
                    ->color('warning')
                    ->tooltip('Reset credentials so the user can enrol a new authenticator')
                    ->visible(fn (User $record): bool => self::canManage()
                        && TwoFactorSettings::isGloballyEnabled()
                        && $record->hasTwoFactorEnabled())
                    ->requiresConfirmation()
                    ->modalHeading('Reset two-factor authentication')
                    ->modalDescription(fn (User $record): string => "This revokes {$record->name}'s current secret and recovery codes. They can set up a new authenticator from their profile page.")
                    ->action(function (User $record): void {
                        $record->resetTwoFactorAuthentication();

                        self::sendSecurityAlert($record, 'Two-factor authentication was reset');

                        // The last super administrator keeps their requirement flag,
                        if ($record->isLastSuperAdmin()) {
                            $record->update(['is_2fa_enabled' => false]);

                            Notification::make()
                                ->title('Two-factor authentication reset')
                                ->body("{$record->name} is the last super administrator, so their two-factor requirement was switched off to keep them able to sign in. They can set up a new authenticator from their profile page.")
                                ->success()
                                ->send();

                            return;
                        }

                        Notification::make()
                            ->title('Two-factor authentication reset')
                            ->body("{$record->name} can set up a new authenticator from their profile page.")
                            ->success()
                            ->send();
                    }),
            ])->recordActionsColumnLabel('Actions') // FOr Display Action Label
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->before(function (DeleteBulkAction $action, Collection $records): void {
                            foreach ($records as $record) {
                                $hasReferences = Product::where('created_by', $record->getKey())->exists()
                                    || Category::where('created_by', $record->getKey())->exists()
                                    || EmailTemplate::where('created_by', $record->getKey())->exists()
                                    || Order::where('created_by', $record->getKey())->exists(); // <-- added order check

                                if ($hasReferences) {
                                    Notification::make()
                                        ->title('Cannot delete')
                                        ->body('This user is recorded as the creator of existing products, categories, email templates or orders.')
                                        ->danger()
                                        ->send();

                                    $action->halt();
                                }
                            }
                        }),
                ]),
            ])
            ->paginated([5, 10, 25, 50, 100])
            ->striped()
            ->defaultSort('created_at', 'desc')
            ->persistFiltersInSession()
            ->persistSortInSession()
            ->persistSearchInSession()
            // Wider dropdowns for column manager and filters with scroll support
            ->columnManagerWidth(Width::ThreeExtraSmall)
            ->columnManagerMaxHeight('200px')
            ->filtersFormWidth(Width::ThreeExtraSmall)
            ->filtersFormMaxHeight('200px')
            // Persist column visibility, order in session
            ->persistColumnsInSession()
            ->loadingSkeleton(); // For defaultskeleton display when filter or tabe click then
    }
}
