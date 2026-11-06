<?php

namespace App\Filament\Resources\Categories\Schemas;

use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class CategoryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Category Details')
                ->schema([
                    TextInput::make('name')->label('Category Name')->required()->maxLength(50),
                    Textarea::make('description')->label('Description')->rows(3)->maxLength(255)->columnSpanFull(),
                    Toggle::make('is_active')->label('Active')->default(true)->required(),
                ])->columnSpanFull(),
        ]);
    }
}
