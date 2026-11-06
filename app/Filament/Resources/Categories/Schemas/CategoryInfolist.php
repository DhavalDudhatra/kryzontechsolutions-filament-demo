<?php

namespace App\Filament\Resources\Categories\Schemas;

use App\Support\DateFormats;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class CategoryInfolist
{
    public static function configure(Schema $schema): Schema
    {
        $dateTimeFormat = DateFormats::dateTime();

        return $schema->components([
            Section::make('Category Details')
                ->description('View category information and status.')
                ->icon(Heroicon::OutlinedTag)
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
                                ->icon(Heroicon::OutlinedCalendarDays),

                            TextEntry::make('updated_at')
                                ->label('Last Updated')
                                ->dateTime($dateTimeFormat)
                                ->placeholder('N/A')
                                ->icon(Heroicon::OutlinedClock),
                        ]),
                ])
                ->columnSpanFull(),
        ]);
    }
}
