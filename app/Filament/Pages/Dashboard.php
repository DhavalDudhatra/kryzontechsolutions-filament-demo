<?php

namespace App\Filament\Pages;

use App\Filament\Resources\Products\Widgets\ProductsChart;
use App\Filament\Widgets\ProductsStatusPieChart;
use App\Filament\Widgets\StatsOverview;
use App\Support\DateFormats;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Pages\Dashboard\Concerns\HasFiltersForm;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Enums\IconSize;
use Filament\Support\Enums\Size;
use Filament\Support\Enums\VerticalAlignment;
use Filament\Support\Icons\Heroicon;

class Dashboard extends BaseDashboard
{
    use HasFiltersForm;

    protected static ?string $title = 'Overview';

    protected ?string $subheading = 'Welcome back — here is your shop today. Pick a date range below to compare products and new users.';

    public function mount(): void
    {
        $this->filters = null;
    }

    public function filtersForm(Schema $schema): Schema
    {
        $dateTimeFormat = DateFormats::date();

        return $schema->components([
            Section::make('Activity Filters')
                ->description('Choose a date range. Leave both dates empty to view year-to-date activity.')
                ->icon(Heroicon::OutlinedFunnel)
                ->iconSize(IconSize::Medium)
                ->compact()
                ->columnSpanFull()
                ->columns(3)
                ->schema([
                    DatePicker::make('startDate')
                        ->label('Start Date')
                        ->suffixIcon(Heroicon::OutlinedCalendarDays)
                        ->placeholder('Start date')
                        ->native(false)
                        ->displayFormat($dateTimeFormat)
                        ->maxDate(fn (Get $get) => $get('endDate') ?: now()),

                    DatePicker::make('endDate')
                        ->label('End Date')
                        ->suffixIcon(Heroicon::OutlinedCalendarDays)
                        ->placeholder('End date')
                        ->native(false)
                        ->displayFormat($dateTimeFormat)
                        ->minDate(fn (Get $get) => $get('startDate'))
                        ->maxDate(now()),

                    Actions::make([
                        Action::make('search')
                            ->label('Apply Filters')
                            ->icon(Heroicon::OutlinedMagnifyingGlass)
                            ->size(Size::Small)
                            ->action(fn (Dashboard $livewire) => $livewire->applyFilters()),

                        Action::make('clearFilters')
                            ->label('Clear Filters')
                            ->icon(Heroicon::OutlinedXMark)
                            ->color('gray')
                            ->outlined()
                            ->size(Size::Small)
                            ->action(fn (Dashboard $livewire) => $livewire->clearFilters()),
                    ])
                        ->verticalAlignment(VerticalAlignment::End),
                ]),
        ]);
    }

    public function applyFilters(): void
    {
        $this->filters = array_filter($this->getFiltersForm()->getState(), 'filled');

        session()->put($this->getFiltersSessionKey(), $this->filters);
    }

    public function clearFilters(): void
    {
        $this->filters = [];
        $this->getFiltersForm()->fill([]);

        session()->forget($this->getFiltersSessionKey());
    }

    public function getWidgets(): array
    {
        return [
            StatsOverview::class,
            ProductsChart::class,
            ProductsStatusPieChart::class,
        ];
    }

    public function getColumns(): int|array
    {
        return 2;
    }
}
