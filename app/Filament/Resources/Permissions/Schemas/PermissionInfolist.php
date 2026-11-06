<?php

namespace App\Filament\Resources\Permissions\Schemas;

use App\Support\DateFormats;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class PermissionInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('name'),
                TextEntry::make('guard_name')->badge(),
                TextEntry::make('roles.name')->label('Roles')->badge()
                    ->separator(',')
                    ->placeholder('-')
                    ->columnSpanFull(),
                TextEntry::make('created_at')->dateTime(DateFormats::dateTime())->placeholder('-'),
                TextEntry::make('updated_at')->dateTime(DateFormats::dateTime())->placeholder('-'),
            ]);
    }
}
