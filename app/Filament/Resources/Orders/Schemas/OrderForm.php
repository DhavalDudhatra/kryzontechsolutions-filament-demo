<?php

namespace App\Filament\Resources\Orders\Schemas;

use App\Enums\PaymentStatus;
use App\Models\OrderItem;
use App\Models\Product;
use App\Services\OrderService;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Repeater\TableColumn;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\View;
use Filament\Schemas\Components\Wizard;
use Filament\Schemas\Components\Wizard\Step;
use Filament\Schemas\Schema;
use Filament\Support\Enums\FontWeight;
use Filament\Support\Enums\TextSize;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\HtmlString;

class OrderForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Wizard::make([
                Step::make('Customer')
                    ->icon(Heroicon::OutlinedUser)
                    ->description('Who is this order for?')
                    ->schema([
                        Section::make('Customer Information')
                            ->columns(2)
                            ->schema([
                                TextInput::make('customer_name')
                                    ->label('Full Name')
                                    ->placeholder('e.g. Jane Doe')
                                    ->required()
                                    ->maxLength(255)
                                    ->columnSpanFull(),

                                TextInput::make('customer_email')
                                    ->label('Email')
                                    ->placeholder('customer@example.com')
                                    ->email()
                                    ->maxLength(255)
                                    ->prefixIcon('heroicon-m-envelope'),

                                TextInput::make('customer_phone')
                                    ->label('Phone')
                                    ->placeholder('+91 98765 43210')
                                    ->tel()
                                    ->maxLength(30)
                                    ->prefixIcon('heroicon-m-phone'),
                            ]),

                        Section::make('Shipping Details')
                            ->schema([
                                Textarea::make('shipping_address')
                                    ->label('Delivery Address')
                                    ->placeholder('Street, city, state, PIN code')
                                    ->required()
                                    ->rows(3)
                                    ->columnSpanFull(),
                            ]),
                    ]),

                Step::make('Products')
                    ->icon(Heroicon::OutlinedShoppingBag)
                    ->description('Select products and quantities')
                    ->schema([
                        Repeater::make('items')
                            ->hiddenLabel()
                            ->minItems(1)
                            ->required()
                            ->defaultItems(1)
                            ->addActionLabel('Add product')
                            ->reorderable(false)
                            ->live()
                            ->table([
                                TableColumn::make('Product')->markAsRequired(),
                                TableColumn::make('Unit Price')->width('130px'),
                                TableColumn::make('Qty')->width('100px')->markAsRequired(),
                                TableColumn::make('Line Total')->width('140px'),
                            ])
                            ->schema([
                                Hidden::make('id'),
                                Select::make('product_id')
                                    ->hiddenLabel()
                                    ->placeholder('Search products')
                                    ->required()
                                    ->searchable()
                                    ->live()
                                    ->options(fn () => self::availableProducts()
                                        ->limit(50)
                                        ->get()
                                        ->mapWithKeys(fn ($product) => [
                                            $product->id => self::label($product),
                                        ])
                                        ->all())
                                    ->getSearchResultsUsing(fn (string $search) => self::availableProducts()
                                        ->where('name', 'like', "%{$search}%")
                                        ->limit(50)
                                        ->get()
                                        ->mapWithKeys(fn ($product) => [
                                            $product->id => self::label($product),
                                        ])
                                        ->all())

                                    ->getOptionLabelUsing(fn ($value): ?string => (($p = Product::withTrashed()->find($value)) instanceof Product) ? self::label($p) : null)
                                    ->disableOptionsWhenSelectedInSiblingRepeaterItems(),

                                TextEntry::make('unit_price_text')
                                    ->hiddenLabel()
                                    ->alignEnd()
                                    ->color('gray')
                                    ->state(fn (Get $get): string => self::money(self::unitPrice($get))),

                                TextInput::make('quantity')
                                    ->hiddenLabel()
                                    ->placeholder('Qty')
                                    ->required()
                                    ->numeric()
                                    ->integer()
                                    ->minValue(1)
                                    ->default(1)
                                    ->live(onBlur: true)
                                    ->helperText(fn (Get $get) => ($stock = self::availableStock($get)) !== null
                                        ? "Available: {$stock}"
                                        : null)
                                    ->maxValue(fn (Get $get): ?int => self::availableStock($get))
                                    ->rules([
                                        'required',
                                        'integer',
                                        'min:1',
                                        fn (Get $get) => function (string $attribute, $value, \Closure $fail) use ($get) {
                                            $stock = self::availableStock($get);

                                            if ($stock !== null && (int) $value > $stock) {
                                                $fail("Only {$stock} in stock for this product.");
                                            }
                                        },
                                    ])
                                    ->validationMessages([
                                        'required' => 'Enter a quantity.',
                                        'integer' => 'Quantity must be a whole number.',
                                        'min' => 'Quantity must be at least 1.',
                                    ]),

                                TextEntry::make('line_total_text')
                                    ->hiddenLabel()
                                    ->alignEnd()
                                    ->weight(FontWeight::SemiBold)
                                    ->state(fn (Get $get): string => self::money(
                                        self::unitPrice($get) * (int) $get('quantity')
                                    )),
                            ]),

                        // FOr Order Summary View Components
                        View::make('filament.orders.order-summary')
                            ->viewData(fn (Get $get): array => [
                                'itemsCount' => count($items = is_array($get('items')) ? $get('items') : []),

                                'units' => (int) collect($items)->sum(
                                    fn (array $item): int => (int) ($item['quantity'] ?? 0)
                                ),

                                'total' => collect($items)->sum(
                                    fn (array $item): float => (float) (self::productsByIds([$item['product_id'] ?? null])[(int) ($item['product_id'] ?? 0)]?->price ?? 0)
                                        * (int) ($item['quantity'] ?? 0)
                                ),
                            ])
                            ->columnSpanFull(),
                    ]),

                Step::make('Payment & Review')
                    ->icon(Heroicon::OutlinedCreditCard)
                    ->description('Payment details and final review')
                    ->schema([
                        Section::make('Payment Method')
                            ->schema([
                                Select::make('payment_type')
                                    ->label('Payment Type')
                                    ->options([
                                        'cash' => 'Cash',
                                        'card' => 'Credit/Debit Card',
                                    ])
                                    ->required()
                                    ->default('cash')
                                    ->native(false)
                                    ->live(),
                            ]),

                        Section::make('Card Details')
                            ->description('Enter your card information')
                            ->columns(2)
                            ->visible(fn (Get $get) => $get('payment_type') === 'card')
                            ->schema([
                                TextInput::make('card_number')
                                    ->label('Card Number')
                                    ->placeholder('XXXX XXXX XXXX XXXX')
                                    ->numeric()
                                    ->required()
                                    ->maxLength(16)
                                    ->minLength(13)
                                    ->rules([
                                        fn () => function (string $attribute, $value, \Closure $fail) {
                                            $number = preg_replace('/\D/', '', $value);
                                            $sum = 0;
                                            $shouldDouble = false;
                                            for ($i = strlen($number) - 1; $i >= 0; $i--) {
                                                $digit = (int) $number[$i];
                                                if ($shouldDouble) {
                                                    if (($digit *= 2) > 9) {
                                                        $digit -= 9;
                                                    }
                                                }
                                                $sum += $digit;
                                                $shouldDouble = ! $shouldDouble;
                                            }
                                            if ($sum % 10 !== 0) {
                                                $fail('The card number provided is invalid.');
                                            }
                                        },
                                    ]),

                                TextInput::make('card_expiry')
                                    ->label('Expiry Date')
                                    ->placeholder('MM/YY')
                                    ->required()
                                    ->maxLength(5)
                                    ->regex('/^(0[1-9]|1[0-2])\/\d{2}$/')
                                    ->validationMessages([
                                        'regex' => 'Expiry must be in MM/YY format.',
                                    ]),

                                TextInput::make('card_cvv')
                                    ->label('CVV')
                                    ->placeholder('123')
                                    ->numeric()
                                    ->required()
                                    ->maxLength(3)
                                    ->minLength(3),

                                Select::make('payment_status')
                                    ->label('Payment Status')
                                    ->options(PaymentStatus::class)
                                    ->default(PaymentStatus::Unpaid->value)
                                    ->required()
                                    ->native(false),
                            ]),

                        Section::make('Pricing')
                            ->columns(2)
                            ->schema([
                                TextInput::make('shipping_amount')
                                    ->label('Shipping Amount')
                                    ->numeric()
                                    ->minValue(0)
                                    ->default(0)
                                    ->required()
                                    ->prefix('₹')
                                    ->live(onBlur: true),

                                TextEntry::make('total_preview')
                                    ->label('Order Total')
                                    ->weight(FontWeight::Bold)
                                    ->size(TextSize::Large)
                                    ->state(fn (Get $get) => self::money(
                                        OrderService::previewSubtotal($get('items'))
                                        + max(0, (float) $get('shipping_amount'))
                                    )),
                            ]),

                    ]),
            ])
                ->skippable(fn (string $operation) => $operation === 'edit')
                ->submitAction(new HtmlString(Blade::render(
                    '<x-filament::button type="submit" size="sm">Save Order</x-filament::button>'
                )))
                ->columnSpanFull(),
        ]);
    }

    /** @var array<int, Product|null> */
    private static array $productsById = [];

    /** @return Builder<Product> */
    private static function availableProducts(): Builder
    {
        return Product::query()->where('is_active', true)->where('stock_quantity', '>', 0)->orderBy('name');
    }

    private static function label(Product $product): string
    {
        return "{$product->name} (Stock: {$product->stock_quantity})";
    }

    private static function productById(mixed $id): ?Product
    {
        if (! is_numeric($id)) {
            return null;
        }

        $key = (int) $id;
        if (! array_key_exists($key, self::$productsById)) {
            self::$productsById = array_replace(self::$productsById, self::productsByIds([$key]));
        }

        return self::$productsById[$key] ?? null;
    }

    /** @param array<int, mixed> $ids
     * @return array<int, Product|null> */
    private static function productsByIds(array $ids): array
    {
        $validIds = array_values(array_unique(array_filter(array_map(
            static fn (mixed $id): int => (int) $id,
            $ids,
        ), static fn (int $id): bool => $id > 0)));

        $missingIds = array_values(array_diff($validIds, array_keys(self::$productsById)));
        if ($missingIds !== []) {
            foreach ($missingIds as $id) {
                self::$productsById[$id] = null;
            }

            foreach (Product::withTrashed()->whereIn('id', $missingIds)->get() as $product) {
                self::$productsById[(int) $product->getKey()] = $product;
            }
        }

        return array_intersect_key(self::$productsById, array_flip($validIds));
    }

    private static function unitPrice(Get $get): float
    {
        return (float) (self::productById($get('product_id'))?->price ?? 0);
    }

    private static function money(float $amount): string
    {
        return '₹ '.number_format($amount, 2);
    }

    private static function availableStock(Get $get): ?int
    {
        $product = self::productById($get('product_id'));

        if (! $product instanceof Product) {
            return null;
        }

        $held = $get('id')
            ? (int) OrderItem::whereKey($get('id'))->where('product_id', $product->id)->value('committed_quantity')
            : 0;

        return (int) $product->stock_quantity + $held;
    }
}
