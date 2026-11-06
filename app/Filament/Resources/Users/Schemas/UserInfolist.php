<?php

namespace App\Filament\Resources\Users\Schemas;

use App\Support\DateFormats;
use App\Support\TwoFactorSettings;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;



class UserInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('User Details')
                ->description('View user information and assigned role.')
                ->icon(Heroicon::OutlinedUser)
                ->schema([
                    Grid::make(['default' => 1, 'md' => 2])
                        ->schema([
                            TextEntry::make('name')->label('Full Name')->placeholder('-'),
                            TextEntry::make('email')->label('Email Address')->placeholder('-'),
                            TextEntry::make('createdBy.name')->label('Created By')->placeholder('-'),
                            TextEntry::make('roles.name')->label('Role')->badge()->separator(',')->placeholder('No role assigned'),
                            TextEntry::make('two_factor_status')->label('Two-factor')->badge()
                                ->visible(fn (): bool => TwoFactorSettings::isGloballyEnabled())
                                ->state(fn ($record): string => $record->twoFactorStatus())
                                ->formatStateUsing(fn (string $state): string => match ($state) {
                                    'enabled' => 'Enabled',
                                    'pending' => 'Pending',
                                    default => 'Disabled',
                                })
                                ->color(fn (string $state): string => match ($state) {
                                    'enabled' => 'success',
                                    'pending' => 'warning',
                                    default => 'danger',
                                })
                                ->icon(fn (string $state): string => match ($state) {
                                    'enabled' => 'heroicon-o-shield-check',
                                    'pending' => 'heroicon-o-exclamation-triangle',
                                    default => 'heroicon-o-shield-exclamation',
                                }),

                            TextEntry::make('created_at')->label('Created At')->dateTime(DateFormats::dateTime())->placeholder('-'),
                            TextEntry::make('updated_at')->label('Last Updated')->dateTime(DateFormats::dateTime())->placeholder('-'),
                        ])->columnSpanFull(),
                ])->columnSpanFull(),
        ]);
    }
}
