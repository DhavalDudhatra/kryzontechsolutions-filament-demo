<?php

namespace App\Filament\Resources\Categories\Tables;

use App\Models\Category;
use App\Support\DateFormats;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Grid;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class CategoriesTable
{
    public static function configure(Table $table): Table
    {
        $dateTimeFormat = DateFormats::dateTime();

        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Category Name')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('description')
                    ->limit(50)
                    ->searchable()
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('createdBy.name')
                    ->label('Created By')
                    ->placeholder('-')
                    ->sortable()
                    ->toggleable(),

                ToggleColumn::make('is_active')
                    ->label('Status')
                    ->alignCenter()
                    ->onColor('success')
                    ->offColor('danger')
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('created_at')
                    ->dateTime($dateTimeFormat)
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('updated_at')
                    ->dateTime($dateTimeFormat)
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->headerActions([
                self::createAction(),
            ])
            ->recordActions([
                self::viewAction($dateTimeFormat),
                self::editAction(),
                self::deleteAction(),
            ])
            ->recordActionsColumnLabel('Actions')
            ->toolbarActions([
                BulkActionGroup::make([
                    self::bulkDeleteAction(),
                ]),
            ])
            ->paginated([5, 10, 25, 50, 100])
            ->striped()
            ->defaultSort('created_at', 'desc')
            ->columnManagerWidth(Width::ThreeExtraSmall)
            ->columnManagerMaxHeight('200px')
            ->persistColumnsInSession()
            ->loadingSkeleton();
    }

    /** Shared form schema for create + edit. */
    private static function formSchema(): array
    {
        return [
            TextInput::make('name')
                ->label('Category Name')
                ->required()
                ->maxLength(255),

            Toggle::make('is_active')
                ->label('Active')
                ->default(true)
                ->required(),

            Textarea::make('description')
                ->label('Description')
                ->columnSpanFull(),
        ];
    }

    // Create Category Button
    private static function createAction(): CreateAction
    {
        return CreateAction::make()
            ->label('Add Category')
            ->icon(Heroicon::OutlinedPlus)
            ->modal()
            ->modalHeading('Create Category')
            ->modalDescription('Enter the new category details.')
            ->modalSubmitActionLabel('Create Category')
            ->schema(self::formSchema())
            ->createAnother(false);
    }

    // View Category Button
    private static function viewAction(string $dateTimeFormat): ViewAction
    {
        return ViewAction::make()
            ->iconButton()
            ->modal()
            ->modalWidth(Width::TwoExtraLarge)
            ->modalHeading(fn (Category $record): string => $record->name)
            ->modalDescription('View Category Details.')
            ->modalSubmitAction(false)
            ->schema([
                Grid::make(['default' => 1, 'md' => 2])
                    ->schema([
                        TextEntry::make('name')
                            ->label('Category Name')
                            ->placeholder('N/A')
                            ->weight('bold'),

                        IconEntry::make('is_active')
                            ->label('Status')
                            ->boolean()
                            ->trueIcon(Heroicon::OutlinedCheckCircle)
                            ->falseIcon(Heroicon::OutlinedXCircle)
                            ->trueColor('success')
                            ->falseColor('danger'),

                        TextEntry::make('description')
                            ->label('Description')
                            ->placeholder('No description available')
                            ->columnSpanFull(),

                        TextEntry::make('created_at')
                            ->label('Created At')
                            ->dateTime($dateTimeFormat)
                            ->placeholder('N/A')
                            ->icon(Heroicon::OutlinedCalendar),

                        TextEntry::make('updated_at')
                            ->label('Last Updated')
                            ->dateTime($dateTimeFormat)
                            ->placeholder('N/A')
                            ->icon(Heroicon::OutlinedClock),

                        TextEntry::make('createdBy.name')
                            ->label('Created By')
                            ->placeholder('-'),
                    ]),
            ]);
    }

    // Edit Category Button
    private static function editAction(): EditAction
    {
        return EditAction::make()
            ->iconButton()
            ->modal()
            ->modalHeading('Edit Category')
            ->modalDescription('Update the category details.')
            ->modalSubmitActionLabel('Save Changes')
            ->schema(self::formSchema());
    }

    // Delete Category Button
    private static function deleteAction(): DeleteAction
    {
        return DeleteAction::make()
            ->iconButton()
            ->before(function (DeleteAction $action, Model $record): void {
                if (self::canDelete($record)) {
                    return;
                }

                self::notifyCannotDelete();
                $action->halt();
            });
    }

    // Multiple Delete Category Button
    private static function bulkDeleteAction(): DeleteBulkAction
    {
        return DeleteBulkAction::make()
            ->before(function (DeleteBulkAction $action, Collection $records): void {
                if (! $records->contains(fn (Model $record): bool => ! self::canDelete($record))) {
                    return;
                }

                self::notifyCannotDelete();
                $action->halt();
            });
    }

    /** Guard against deleting a category that still has products. */
    private static function canDelete(Model $record): bool
    {
        return $record instanceof Category
            && ! $record->products()->exists();
    }

    private static function notifyCannotDelete(): void
    {
        Notification::make()
            ->title('Cannot delete category')
            ->body('This category still has products assigned to it.')
            ->danger()
            ->send();
    }
}
