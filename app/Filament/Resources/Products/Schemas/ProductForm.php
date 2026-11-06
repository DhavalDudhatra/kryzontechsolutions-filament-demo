<?php

namespace App\Filament\Resources\Products\Schemas;

use App\Filament\Support\FileUploadConfig;
use Filament\Forms\Components\MarkdownEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ProductForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Product Details')
                ->schema([
                    TextInput::make('name')
                        ->required()
                        ->maxLength(100),

                    TextInput::make('price')
                        ->required()
                        ->numeric()
                        ->minValue(0)
                        ->prefix('$'),

                    FileUploadConfig::productImage(),

                    Select::make('category_id')
                        ->label('Category')
                        ->relationship('category', 'name')
                        ->searchable()
                        ->preload()
                        ->required(),

                    TextInput::make('stock_quantity')
                        ->label('Available Stock')
                        ->integer()
                        ->minValue(0)
                        ->required()
                        ->default(0),

                    TextInput::make('low_stock_threshold')
                        ->label('Low Stock Alert')
                        ->integer()
                        ->minValue(0)
                        ->required()
                        ->default(5),

                    MarkdownEditor::make('description')
                        ->toolbarButtons([
                            ['bold', 'italic', 'strike', 'link'],
                            ['heading'],
                            ['blockquote', 'codeBlock', 'bulletList', 'orderedList'],
                            ['table', 'attachFiles'],
                            ['undo', 'redo'],
                        ])
                        ->minHeight('12rem')
                        ->maxHeight('24rem')
                        ->columnSpanFull(),

                    Toggle::make('is_active')
                        ->required()
                        ->default(true),
                ])
                ->columns(2)
                ->columnSpanFull(),
        ]);
    }
}
