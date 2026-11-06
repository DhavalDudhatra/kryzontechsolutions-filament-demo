<?php

namespace App\Filament\Resources\Permissions\Tables;

use App\Support\DateFormats;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Support\Enums\Width;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PermissionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->searchable()->sortable()->toggleable(),
                TextColumn::make('guard_name')->badge()->sortable()->toggleable(),
                TextColumn::make('roles_count')->counts('roles')->label('Roles')->badge()->toggleable(),
                TextColumn::make('created_at')->dateTime(DateFormats::dateTime())->sortable()->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')->dateTime(DateFormats::dateTime())->sortable()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
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
            ->columnManagerMaxHeight('400px')
            // Persist column visibility, order in session
            ->persistColumnsInSession();
    }
}
