<?php

namespace App\Filament\Resources\Roles\Schemas;

use App\Enums\Permission as PermissionEnum;
use App\Support\DateFormats;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Spatie\Permission\Models\Permission;


class RoleInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                // Role Information Section
                Section::make('Role Information')
                    ->description('View role details and assigned permissions.')
                    ->icon(Heroicon::OutlinedShieldCheck)
                    ->schema([
                        Grid::make(['default' => 1, 'md' => 2])
                            ->schema([
                                TextEntry::make('name')->label('Role Name')
                                    ->icon(Heroicon::OutlinedIdentification)
                                    ->weight('bold')
                                    ->placeholder('-'),

                                TextEntry::make('guard_name')->label('Guard')->badge()
                                    ->color('info')
                                    ->icon(Heroicon::OutlinedLockClosed)
                                    ->placeholder('-'),

                                TextEntry::make('created_at')->label('Created At')
                                    ->dateTime(DateFormats::dateTime())
                                    ->icon(Heroicon::OutlinedCalendar)
                                    ->placeholder('-'),

                                TextEntry::make('updated_at')->label('Last Updated')
                                    ->dateTime(DateFormats::dateTime())
                                    ->icon(Heroicon::OutlinedClock)
                                    ->placeholder('-'),
                            ]),
                    ])->columnSpanFull(),

                // Assign Permission Section
                Section::make('Assigned Permissions')
                    ->description('Review the permissions granted to this role.')
                    ->icon(Heroicon::OutlinedKey)
                    ->collapsible()
                    ->schema([
                        TextEntry::make('permissions')->label('Role Permissions')
                            ->state(fn ($record): array => $record->permissions
                                ->sortBy('name')
                                ->map(fn (Permission $permission): string => self::permissionLabel($permission))
                                ->values()
                                ->all()
                            )
                            ->badge()
                            ->color('info')
                            ->placeholder('No permissions assigned to this role.')
                            ->columnSpanFull(),
                    ])->columnSpanFull(),
            ]);
    }

    private static function permissionLabel(Permission $permission): string
    {
        $case = PermissionEnum::tryFrom($permission->name);

        return $case instanceof PermissionEnum ? "{$case->group()}: {$case->label()}" : $permission->name;
    }
}
