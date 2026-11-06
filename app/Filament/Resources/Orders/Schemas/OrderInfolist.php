<?php

namespace App\Filament\Resources\Orders\Schemas;

use App\Support\DateFormats;
use BackedEnum;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\RepeatableEntry\TableColumn;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\FontWeight;
use Filament\Support\Enums\TextSize;

class OrderInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()
                ->compact()
                ->columnSpanFull()
                ->schema([
                    Grid::make(['default' => 1, 'md' => 3])
                        ->schema([
                            TextEntry::make('order_number')
                                ->label('Order ID')
                                ->weight(FontWeight::Bold)
                                ->copyable()
                                ->color('primary'),

                            TextEntry::make('total_amount')
                                ->label('Total')
                                ->money('INR')
                                ->weight(FontWeight::Bold)
                                ->color('primary'),

                            TextEntry::make('status')
                                ->label('Order Status')
                                ->badge()
                                ->color(fn ($state) => match ($state instanceof BackedEnum ? $state->value : (string) $state) {
                                    'pending' => 'warning',
                                    'processing' => 'info',
                                    'completed' => 'success',
                                    'cancelled' => 'danger',
                                    default => 'gray',
                                }),

                            TextEntry::make('payment_status')
                                ->label('Payment Status')
                                ->badge()
                                ->color(fn ($state) => match ($state instanceof BackedEnum ? $state->value : (string) $state) {
                                    'unpaid' => 'danger',
                                    'paid' => 'success',
                                    default => 'gray',
                                }),

                            TextEntry::make('created_at')
                                ->label('Order Date')
                                ->dateTime(DateFormats::dateTime())
                                ->weight(FontWeight::Bold)
                                ->color('primary')
                                ->copyable(),
                        ]),
                ]),

            Grid::make(['default' => 1, 'lg' => 3])
                ->columnSpanFull()
                ->schema([
                    Group::make([
                        Section::make('Customer Details')
                            ->compact()
                            ->schema([
                                TextEntry::make('customer_name')
                                    ->label('Customer Name')
                                    ->weight(FontWeight::SemiBold),

                                TextEntry::make('customer_email')
                                    ->label('Customer Email')
                                    ->copyable(),

                                TextEntry::make('customer_phone')
                                    ->label('Customer Phone')
                                    ->copyable(),

                                TextEntry::make('shipping_address')->label('Address'),

                                TextEntry::make('tracking_number')
                                    ->label('Tracking')
                                    ->copyable()
                                    ->visible(fn ($record) => filled($record->tracking_number)),
                            ])->columns(3),
                    ])->columnSpanFull(),
                    Section::make('Biling Details')
                        ->compact()
                        ->columnSpan(3)
                        ->schema([
                            RepeatableEntry::make('items')
                                ->hiddenLabel()
                                ->table([
                                    TableColumn::make('Product'),
                                    TableColumn::make('Qty')->alignment('end'),
                                    TableColumn::make('Amount')->alignment('end'),
                                ])
                                ->schema([
                                    TextEntry::make('product_name')
                                        ->hiddenLabel()
                                        ->weight(FontWeight::SemiBold),

                                    TextEntry::make('quantity')
                                        ->hiddenLabel()
                                        ->numeric()
                                        ->alignment('end'),

                                    TextEntry::make('line_subtotal')
                                        ->hiddenLabel()
                                        ->money('INR')
                                        ->alignment('end')
                                        ->weight(FontWeight::Bold),
                                ]),

                            Grid::make(1)
                                ->schema([
                                    TextEntry::make('subtotal')
                                        ->label('Subtotal')
                                        ->money('INR')
                                        ->alignment('end')
                                        ->inlineLabel(),

                                    TextEntry::make('shipping_amount')
                                        ->label('Shipping')
                                        ->money('INR')
                                        ->alignment('end')
                                        ->inlineLabel(),

                                    TextEntry::make('total_amount')
                                        ->label('Grand Total')
                                        ->money('INR')
                                        ->inlineLabel()
                                        ->alignment('end')
                                        ->weight(FontWeight::Bold)
                                        ->size(TextSize::Large)
                                        ->color('primary')
                                        ->extraAttributes([
                                            'class' => 'border-t border-gray-200 pt-3 mt-2',
                                        ]),
                                ])
                                ->columnSpanFull()
                                ->extraAttributes([
                                    'class' => 'ml-auto w-full max-w-md',
                                ]),
                        ]),
                ]),

            Section::make('Admin Notes')
                ->compact()
                ->columnSpanFull()
                ->visible(fn ($record) => filled($record->notes))
                ->schema([
                    TextEntry::make('notes')
                        ->label('Notes')
                        ->color('gray'),
                ]),
        ]);
    }
}
