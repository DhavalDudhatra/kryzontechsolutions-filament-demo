<?php

namespace App\Filament\Resources\Orders\Schemas;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Support\DateFormats;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Repeater\TableColumn;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;
use Filament\Support\Enums\FontWeight;
use Illuminate\Database\Eloquent\Model;

class OrderEditForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()
                ->visible(fn (?Model $record): bool => self::isLocked($record))
                ->compact()
                ->schema([
                    TextEntry::make('locked_notice')
                        ->hiddenLabel()
                        ->color('danger')
                        ->weight(FontWeight::Bold)
                        ->state('This order is cancelled and cannot be edited.')
                        ->columnSpanFull(),
                ])
                ->columnSpanFull(),

            Grid::make(['default' => 1, 'lg' => 3])
                ->columnSpanFull()
                ->disabled(fn (?Model $record): bool => self::isLocked($record))
                ->schema([
                    Group::make([
                        Section::make('Order Items')
                            ->compact()
                            ->schema([
                                Repeater::make('items')
                                    ->hiddenLabel()
                                    ->table([
                                        TableColumn::make('Product')->markAsRequired(),
                                        TableColumn::make('Price')->width('120px'),
                                        TableColumn::make('Qty')->width('90px')->markAsRequired(),
                                        TableColumn::make('Total')->width('130px'),
                                    ])
                                    ->schema([
                                        Hidden::make('id'),
                                        Hidden::make('unit_price')->dehydrated(false),

                                        Select::make('product_id')
                                            ->hiddenLabel()
                                            ->placeholder('Select product')
                                            ->options(fn (): array => Product::query()
                                                ->where('is_active', true)
                                                ->where('stock_quantity', '>', 0)
                                                ->orderBy('name')
                                                ->get()
                                                ->mapWithKeys(fn (Product $product): array => [
                                                    $product->id => self::label($product),
                                                ])
                                                ->all())
                                            ->getOptionLabelUsing(fn ($value): ?string => (($product = Product::withTrashed()->find($value)) instanceof Product)
                                                ? self::label($product)
                                                : null)
                                            ->disableOptionsWhenSelectedInSiblingRepeaterItems()
                                            ->searchable()
                                            ->required()
                                            ->rules(['exists:products,id'])
                                            ->live()
                                            ->afterStateUpdated(function (Get $get, Set $set): void {
                                                $product = Product::query()
                                                    ->where('is_active', true)
                                                    ->where('stock_quantity', '>', 0)
                                                    ->find($get('product_id'));

                                                if (! $product instanceof Product) {
                                                    $set('product_id', null);
                                                    $set('unit_price', 0);

                                                    return;
                                                }

                                                $set('unit_price', (float) $product->price);
                                            }),

                                        TextEntry::make('price_text')
                                            ->hiddenLabel()
                                            ->alignEnd()
                                            ->color('gray')
                                            ->state(fn (Get $get): string => self::money((float) $get('unit_price'))),

                                        TextInput::make('quantity')
                                            ->hiddenLabel()
                                            ->placeholder('Qty')
                                            ->numeric()
                                            ->integer()
                                            ->minValue(1)
                                            ->default(1)
                                            ->required()
                                            ->live(onBlur: true)
                                            ->rules(['required', 'integer', 'min:1'])
                                            ->maxValue(function (Get $get, ?Model $record): ?int {
                                                $product = Product::withTrashed()->find($get('product_id'));
                                                if (! $product instanceof Product) {
                                                    return null;
                                                }

                                                $recordOrder = $record instanceof Order ? $record : null;
                                                $existing = $recordOrder?->items->firstWhere('id', $get('id'));
                                                $held = ($existing instanceof OrderItem && (int) $existing->product_id === (int) $product->id)
                                                    ? (int) $existing->committed_quantity
                                                    : 0;

                                                return (int) $product->stock_quantity + $held;
                                            })
                                            ->validationMessages([
                                                'required' => 'Enter a quantity.',
                                                'integer' => 'Quantity must be a whole number.',
                                                'min' => 'Quantity must be at least 1.',
                                                'max' => 'The requested quantity exceeds available stock.',
                                            ]),

                                        TextEntry::make('total_text')
                                            ->hiddenLabel()
                                            ->alignEnd()
                                            ->weight(FontWeight::SemiBold)
                                            ->state(fn (Get $get): string => self::money(
                                                (float) $get('unit_price') * (int) $get('quantity')
                                            )),
                                    ])
                                    ->addActionLabel('Add product')
                                    ->reorderable(false)
                                    ->minItems(1)
                                    ->live(),

                                // FOr Order Summary View Components
                                View::make('filament.orders.order-summary')
                                    ->viewData(fn (Get $get): array => [
                                        'itemsCount' => count($items = is_array($get('items')) ? $get('items') : []),

                                        'units' => (int) collect($items)->sum(
                                            fn (array $item): int => (int) ($item['quantity'] ?? 0)
                                        ),

                                        'total' => (float) collect($items)->sum(
                                            fn (array $item): float => (
                                                (float) (Product::withTrashed()->find($item['product_id'] ?? null)?->price ?? 0)
                                                * (int) ($item['quantity'] ?? 0)
                                            )
                                        ),
                                    ])
                                    ->columnSpanFull(),
                            ]),

                        Section::make('Notes')
                            ->compact()
                            ->schema([
                                Textarea::make('notes')
                                    ->hiddenLabel()
                                    ->placeholder('Add notes about this order...')
                                    ->rows(3)
                                    ->columnSpanFull(),
                            ]),
                    ])->columnSpan(['lg' => 2]),

                    Group::make([
                        Section::make('Customer Details')
                            ->compact()
                            ->schema([
                                TextInput::make('customer_name')
                                    ->label('Customer Name')
                                    ->placeholder('Enter customer name')
                                    ->required(),

                                TextInput::make('customer_email')
                                    ->label('Email Address')
                                    ->placeholder('customer@example.com')
                                    ->email()
                                    ->required(),
                            ]),

                        Section::make('Order Details')
                            ->compact()
                            ->schema([
                                TextEntry::make('status')
                                    ->label('Status')
                                    ->badge()
                                    ->color(fn ($state) => match ($state) {
                                        'pending' => 'warning',
                                        'processing' => 'info',
                                        'completed' => 'success',
                                        'cancelled' => 'danger',
                                        default => 'gray',
                                    }),

                                TextEntry::make('created_at')
                                    ->label('Created On')
                                    ->color('gray')
                                    ->dateTime(DateFormats::dateTime()),

                                TextEntry::make('order_id')
                                    ->label('Order Number')
                                    ->weight(FontWeight::Bold)
                                    ->state(fn (?Model $record): string => $record instanceof Order ? $record->order_number : 'N/A'),
                            ]),
                    ])->columnSpan(['lg' => 1]),
                ]),
        ]);
    }

    public static function isLocked(?Model $record): bool
    {
        return $record instanceof Order && $record->status === OrderStatus::Cancelled;
    }

    private static function money(float $amount): string
    {
        return '₹ '.number_format($amount, 2);
    }

    private static function label(Product $product): string
    {
        return "{$product->name} — Stock: {$product->stock_quantity}";
    }
}
