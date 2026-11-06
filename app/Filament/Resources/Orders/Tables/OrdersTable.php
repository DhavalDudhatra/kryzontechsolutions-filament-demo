<?php

namespace App\Filament\Resources\Orders\Tables;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Filament\Actions\DownloadOrderPdfAction;
use App\Filament\Resources\Orders\OrderResource;
use App\Models\Order;
use App\Services\OrderService;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Forms\Components\DatePicker;
use Filament\Notifications\Notification;
use Filament\Support\Enums\IconPosition;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class OrdersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('order_number')
                    ->searchable()
                    ->sortable()
                    ->copyable(),

                TextColumn::make('customer_name')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('customer_email')
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('total_amount')
                    ->money('INR')
                    ->sortable(),

                TextColumn::make('status')
                    ->badge()
                    ->sortable()
                    ->color(fn (OrderStatus $state): string => match ($state) {
                        OrderStatus::Pending => 'gray',
                        OrderStatus::Processing => 'info',
                        OrderStatus::Completed => 'success',
                        OrderStatus::Cancelled => 'danger',
                    }),

                TextColumn::make('payment_status')
                    ->badge()
                    ->sortable(),

                TextColumn::make('items_count')
                    ->counts('items')
                    ->label('Items'),

                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')

            ->filters([
                SelectFilter::make('status')
                    ->options(OrderStatus::class),

                SelectFilter::make('payment_status')
                    ->options(PaymentStatus::class),

                Filter::make('created_at')
                    ->schema([
                        DatePicker::make('from'),
                        DatePicker::make('until'),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query
                        ->when(
                            $data['from'] ?? null,
                            fn (Builder $query, $date): Builder => $query->whereDate('created_at', '>=', $date)
                        )
                        ->when(
                            $data['until'] ?? null,
                            fn (Builder $query, $date): Builder => $query->whereDate('created_at', '<=', $date)
                        )),
            ])

            ->recordActions([
                Action::make('view')
                    ->icon(Heroicon::Eye)
                    ->iconButton()
                    ->url(fn (Order $record): string => OrderResource::getUrl('view', [
                        'record' => $record,
                    ])),

                Action::make('edit')
                    ->icon(Heroicon::PencilSquare)
                    ->iconButton()
                    ->url(fn (Order $record): string => OrderResource::getUrl('edit', [
                        'record' => $record,
                    ])),

                DownloadOrderPdfAction::make(),

                ActionGroup::make(
                    array_map(
                        fn (OrderStatus $status): Action => Action::make('status_'.$status->value)
                            ->label($status->getLabel())
                            ->visible(fn (Order $record): bool => in_array(
                                $status,
                                $record->status->allowedTransitions(),
                                true,
                            ))
                            ->disabled(fn (Order $record): bool => in_array(
                                $record->status,
                                [
                                    OrderStatus::Cancelled,
                                    OrderStatus::Completed,
                                ],
                                true,
                            ))
                            ->action(function (Order $record) use ($status): void {
                                $oldStatus = $record->status->getLabel();

                                app(OrderService::class)->update($record, [
                                    'status' => $status->value,
                                ]);

                                $record->refresh();

                                Notification::make()
                                    ->title('Order status updated')
                                    ->body("Order changed from {$oldStatus} to {$record->status->getLabel()}.")
                                    ->success()
                                    ->send();
                            }),
                        OrderStatus::cases(),
                    )
                )
                    ->label('Change Status')
                    ->icon(Heroicon::ChevronDown)
                    ->iconPosition(IconPosition::After)
                    ->size('xs')
                    ->button()
                    ->dropdown(),
            ])

            ->recordActionsColumnLabel('Actions')
            ->emptyStateHeading('No orders yet')
            ->emptyStateDescription('Create your first order to get started.')
            ->columnManagerWidth(Width::ThreeExtraSmall)
            ->columnManagerMaxHeight('200px')
            ->filtersFormWidth(Width::ThreeExtraSmall)
            ->filtersFormMaxHeight('200px');
    }
}
