<?php

namespace App\Filament\Resources\Products\Tables;

use App\Enums\Permission;
use App\Filament\Exports\ProductExporter;
use App\Imports\ProductsImport;
use App\Models\Category;
use App\Models\Product;
use App\Support\DateFormats;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Throwable;


class ProductsTable
{
    public static function configure(Table $table): Table
    {
        $dateTimeFormat = DateFormats::dateTime();

        return $table
            ->headerActions([
                Action::make('exportProducts')
                    ->label('Export Excel')
                    ->icon(Heroicon::OutlinedArrowDownTray)
                    ->authorize(Permission::ProductsView->value)
                    ->action(fn (HasTable $livewire) => ProductExporter::download(
                        $livewire->getTableQueryForExport(),
                        'products-'.now()->format('Y-m-d_His').'.xlsx',
                    )),

                self::importAction(),
            ])
            ->columns([
                ImageColumn::make('image')
                    ->label('Image')
                    ->disk('public')
                    ->imageSize(50)
                    ->square()
                    ->defaultImageUrl(
                        fn (Product $record): ?string => blank($record->image)
                            ? asset('images/no_images.jpeg')
                            : null,
                    )
                    ->toggleable(),

                TextColumn::make('name')
                    ->sortable()
                    ->searchable()
                    ->weight('medium')
                    ->toggleable(),

                TextColumn::make('category.name')
                    ->label('Category')
                    ->badge()
                    ->sortable(query: fn (Builder $query, string $direction): Builder => $query->orderBy(
                        Category::query()
                            ->select('name')
                            ->whereColumn('categories.id', 'products.category_id'),
                        $direction === 'desc' ? 'desc' : 'asc',
                    ))
                    ->searchable(
                        query: fn (Builder $query, string $search): Builder => $query->whereHas(
                            'category',
                            fn (Builder $query): Builder => $query->where('name', 'like', "%{$search}%"),
                        ),
                    )
                    ->toggleable(),

                TextColumn::make('price')
                    ->money()
                    ->sortable()
                    ->alignEnd()
                    ->toggleable(),

                TextColumn::make('stock_quantity')
                    ->label('Stock')
                    ->sortable()
                    ->badge()
                    ->color(fn ($state): string => match (true) {
                        (int) $state === 0 => 'danger',
                        (int) $state <= 5 => 'warning',
                        default => 'success',
                    }),

                TextColumn::make('description')
                    ->formatStateUsing(fn ($state): string => strip_tags((string) $state))
                    ->limit(60)
                    ->tooltip(function (TextColumn $column): ?string {
                        $description = strip_tags((string) $column->getState());

                        return mb_strlen($description) > 60 ? $description : null;
                    })
                    ->toggleable(isToggledHiddenByDefault: true),

                ToggleColumn::make('is_active')
                    ->label('Status')
                    ->alignCenter()
                    ->onColor('success')
                    ->offColor('danger')
                    ->sortable()
                    ->disabled(fn (): bool => Gate::denies(Permission::ProductsUpdate->value))
                    ->toggleable(),

                TextColumn::make('createdBy.name')
                    ->label('Created By')
                    ->placeholder('-')
                    ->searchable()
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
            ->filters([
                SelectFilter::make('is_active')
                    ->label('Status')
                    ->options([
                        1 => 'Active',
                        0 => 'Inactive',
                    ]),

                SelectFilter::make('category')
                    ->relationship('category', 'name')
                    ->label('Category')
                    ->searchable()
                    ->preload(),

                Filter::make('price')
                    ->schema([
                        TextInput::make('min_price')
                            ->label('Minimum Price')
                            ->numeric()
                            ->minValue(0),

                        TextInput::make('max_price')
                            ->label('Maximum Price')
                            ->numeric()
                            ->minValue(0),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                filled($data['min_price'] ?? null),
                                fn (Builder $query): Builder => $query->where(
                                    'price',
                                    '>=',
                                    $data['min_price'],
                                ),
                            )
                            ->when(
                                filled($data['max_price'] ?? null),
                                fn (Builder $query): Builder => $query->where(
                                    'price',
                                    '<=',
                                    $data['max_price'],
                                ),
                            );
                    }),

                Filter::make('low_stock')
                    ->label('Low Stock')
                    ->query(
                        fn (Builder $query): Builder => $query->whereColumn(
                            'stock_quantity',
                            '<=',
                            'low_stock_threshold',
                        ),
                    ),

                Filter::make('created_at')
                    ->schema([
                        DatePicker::make('created_from')
                            ->label('Created From'),

                        DatePicker::make('created_until')
                            ->label('Created Until')
                            ->afterOrEqual('created_from'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                filled($data['created_from'] ?? null),
                                fn (Builder $query): Builder => $query->whereDate(
                                    'created_at',
                                    '>=',
                                    $data['created_from'],
                                ),
                            )
                            ->when(
                                filled($data['created_until'] ?? null),
                                fn (Builder $query): Builder => $query->whereDate(
                                    'created_at',
                                    '<=',
                                    $data['created_until'],
                                ),
                            );
                    }),
            ])
            ->recordActions([
                ViewAction::make()
                    ->iconButton()
                    ->tooltip('View'),

                EditAction::make()
                    ->iconButton()
                    ->tooltip('Edit'),

                DeleteAction::make()
                    ->iconButton()
                    ->tooltip('Delete'),

                RestoreAction::make()
                    ->iconButton()
                    ->tooltip('Restore'),

                ForceDeleteAction::make()
                    ->iconButton()
                    ->tooltip('Permanently delete'),
            ])
            ->recordActionsColumnLabel('Actions')
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('activate')
                        ->label('Mark as active')
                        ->icon(Heroicon::OutlinedCheckCircle)
                        ->color('success')
                        ->requiresConfirmation()
                        ->authorize(Permission::ProductsUpdate->value)
                        ->action(fn (Collection $records) => $records->each->update([
                            'is_active' => true,
                        ]))
                        ->deselectRecordsAfterCompletion(),

                    BulkAction::make('deactivate')
                        ->label('Mark as inactive')
                        ->icon(Heroicon::OutlinedXCircle)
                        ->color('warning')
                        ->requiresConfirmation()
                        ->authorize(Permission::ProductsUpdate->value)
                        ->action(fn (Collection $records) => $records->each->update([
                            'is_active' => false,
                        ]))
                        ->deselectRecordsAfterCompletion(),

                    DeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                ]),
            ])
            ->paginated([5, 10, 25, 50, 100])
            ->striped()
            ->defaultSort('created_at', 'desc')
            ->persistFiltersInSession()
            ->persistSearchInSession()
            ->columnManagerWidth(Width::ThreeExtraSmall)
            ->columnManagerMaxHeight('400px')
            ->filtersFormWidth(Width::ThreeExtraSmall)
            ->filtersFormMaxHeight('200px')
            ->persistColumnsInSession()
            ->emptyStateIcon(Heroicon::OutlinedCube)
            ->emptyStateHeading('No products yet')
            ->emptyStateDescription('Create a product or import a file to get started.');
    }

    private static function importAction(): Action
    {
        return Action::make('importProducts')
            ->label('Import Products')
            ->icon(Heroicon::OutlinedDocumentArrowUp)
            ->authorize(Permission::ProductsCreate->value)
            ->modalHeading('Import Products')
            ->modalSubmitActionLabel('Import')
            ->schema([
                FileUpload::make('file')
                    ->label('Excel or CSV file')
                    ->acceptedFileTypes([
                        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                        'text/csv',
                        'application/csv',
                    ])
                    ->rules(['file', 'extensions:xlsx,csv'])
                    ->maxSize(10240)
                    ->storeFiles(false)
                    ->panelLayout('integrated')
                    ->helperText('Accepted formats: .xlsx, .csv. Maximum size: 10 MB.')
                    ->extraAttributes(['class' => 'import-products-upload'])
                    ->required(),
            ])
            ->action(fn (array $data) => self::importProducts($data['file'] ?? null));
    }

    // Import Products Code
    private static function importProducts(mixed $file): void
    {
        if (! $file instanceof UploadedFile || ! is_readable($file->getPathname())) {
            Notification::make()
                ->title('Import failed')
                ->body('The uploaded file could not be read.')
                ->danger()
                ->send();

            return;
        }

        try {
            $result = app(ProductsImport::class)->import($file);
        } catch (Throwable $exception) {
            report($exception);

            Notification::make()
                ->title('Import failed')
                ->body('An unexpected error occurred while importing products.')
                ->danger()
                ->send();

            return;
        }

        if ($result['failed'] !== []) {
            Notification::make()
                ->title($result['imported'] === 0 ? 'Import failed' : 'Import completed with errors')
                ->body(self::importFailureBody($result['imported'], $result['failed']))
                ->warning()
                ->send();

            return;
        }

        Notification::make()
            ->title('Products imported successfully')
            ->success()
            ->send();
    }

    private static function importFailureBody(int $imported, array $failed): string
    {
        $preview = collect($failed)
            ->take(5)
            ->map(fn (array $row): string => "Row {$row['row']}: ".implode(' ', $row['errors']))
            ->implode(' | ');

        $total = count($failed);
        $body = "{$imported} product(s) imported. {$total} row(s) failed: {$preview}";

        if ($total > 5) {
            $body .= ' …and '.($total - 5).' more row(s).';
        }

        return $body;
    }
}
