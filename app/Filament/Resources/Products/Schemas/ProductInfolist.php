<?php

namespace App\Filament\Resources\Products\Schemas;

use App\Support\DateFormats;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class ProductInfolist
{
    public static function configure(Schema $schema): Schema
    {
        $dateTimeFormat = DateFormats::dateTime();

        return $schema->components([
            Section::make('Product Overview')
                ->icon(Heroicon::OutlinedCube)
                ->description('View product details, status, and stock information.')
                ->schema([
                    Grid::make(['default' => 1, 'md' => 4])
                        ->schema([
                            ImageEntry::make('image')
                                ->label('Product Image')
                                ->disk('public')
                                ->defaultImageUrl(
                                    fn ($record): ?string => blank($record->image)
                                        ? asset('images/no_images.jpeg')
                                        : null,
                                )
                                ->imageHeight(250)
                                ->square()
                                ->extraImgAttributes([
                                    'alt' => 'Product image',
                                    'loading' => 'lazy',
                                ])
                                ->columnSpan(1),

                            Grid::make(2)
                                ->schema([
                                    TextEntry::make('name')
                                        ->label('Product Name')
                                        ->size('xl')
                                        ->weight('bold'),

                                    TextEntry::make('price')
                                        ->label('Selling Price')
                                        ->money('USD')
                                        ->size('xl')
                                        ->weight('bold')
                                        ->color('primary'),

                                    TextEntry::make('category.name')
                                        ->label('Category')
                                        ->badge()
                                        ->placeholder('-'),

                                    TextEntry::make('is_active')
                                        ->label('Product Status')
                                        ->formatStateUsing(fn ($state): string => $state ? 'Active' : 'Inactive')
                                        ->badge()
                                        ->color(fn ($state): string => $state ? 'success' : 'danger'),

                                    TextEntry::make('stock_quantity')
                                        ->label('Available Stock')
                                        ->badge()
                                        ->color(fn ($state): string => match (true) {
                                            (int) $state === 0 => 'danger',
                                            (int) $state <= 5 => 'warning',
                                            default => 'success',
                                        }),

                                    TextEntry::make('low_stock_threshold')
                                        ->label('Low Stock Alert'),

                                    TextEntry::make('createdBy.name')
                                        ->label('Created By')
                                        ->placeholder('-'),

                                    TextEntry::make('created_at')
                                        ->label('Created On')
                                        ->dateTime($dateTimeFormat),

                                    TextEntry::make('updated_at')
                                        ->label('Last Updated')
                                        ->dateTime($dateTimeFormat),
                                ])
                                ->columnSpan(3),
                        ]),
                ])
                ->columnSpanFull(),

            Section::make('Product Description')
                ->icon(Heroicon::OutlinedDocumentText)
                ->schema([
                    TextEntry::make('description')
                        ->hiddenLabel()
                        ->html()
                        ->columnSpanFull(),
                ])
                ->columnSpanFull(),
        ]);
    }
}
