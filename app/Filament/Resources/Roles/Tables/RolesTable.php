<?php

namespace App\Filament\Resources\Roles\Tables;

use App\Support\DateFormats;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
use Filament\Support\Enums\Width;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Collection;
use Spatie\Permission\Models\Role;

class RolesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->searchable()->sortable()->toggleable(),
                TextColumn::make('guard_name')->badge()->sortable()->toggleable(),
                TextColumn::make('permissions_count')->counts('permissions')->label('Permissions')->badge()->toggleable(),
                TextColumn::make('users_count')->counts('users')->label('Users')->badge()->toggleable(),
                TextColumn::make('created_at')->dateTime(DateFormats::dateTime())->sortable()->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')->dateTime(DateFormats::dateTime())->sortable()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([])
            ->recordActions([
                ViewAction::make()->iconButton(),
                EditAction::make()->iconButton(),
            ])->recordActionsColumnLabel('Actions') // FOr Display Action Label
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->before(function (DeleteBulkAction $action, Collection $records): void {
                            $reason = null;

                            /** @var Role $record */
                            foreach ($records as $record) {
                                if ($record->users()->exists()) {
                                    $reason = "The role \"{$record->name}\" is still assigned to users.";

                                    break;
                                }
                            }

                            if ($reason !== null) {
                                Notification::make()
                                    ->title('Cannot delete')
                                    ->body($reason)
                                    ->danger()
                                    ->send();

                                $action->halt();
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
            // Wider dropdowns for column manager with scroll support
            ->columnManagerWidth(Width::ThreeExtraSmall)
            ->columnManagerMaxHeight('200px')
            // Persist column visibility, order in session
            ->persistColumnsInSession()
            ->loadingSkeleton(); // For defaultskeleton display when filter or tabe click then
    }
}
