<?php

namespace App\Filament\Resources\Products\Widgets;

use App\Enums\Permission;
use App\Filament\Widgets\Concerns\FiltersDashboardData;
use App\Filament\Widgets\Concerns\IsNotLazyLoaded;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Illuminate\Support\Facades\Gate;

class ProductsChart extends ChartWidget
{
    // Shared date-range handling and monthly aggregation.
    /** @use FiltersDashboardData<Product> */
    use FiltersDashboardData;

    // Accesses the dashboard's selected filters.
    use InteractsWithPageFilters;
    use IsNotLazyLoaded;

    protected ?string $maxHeight = '300px';

    protected ?string $heading = 'Product creation trend';

    protected ?string $description = 'Products created each month in the selected date range.';

    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 1;

    // Only users with the chart permission see this widget.
    public static function canView(): bool
    {
        return Gate::allows(Permission::WidgetsProductsChart->value);
    }

    // Generates monthly product statistics for the selected date range.
    protected function getData(): array
    {
        [$start, $end] = $this->resolveDateRange(
            $this->pageFilters['startDate'] ?? null,
            $this->pageFilters['endDate'] ?? null,
        );

        $series = $this->monthlySeries($this->dashboardSnapshot($start, $end)['monthly'], $start, $end);

        return [
            'datasets' => [
                [
                    'label' => 'Products',
                    'data' => $series['data'],
                    'borderColor' => '#6366f1',
                    'backgroundColor' => 'rgba(99, 102, 241, 0.12)',
                    'fill' => true,
                    'tension' => 0.35,
                    'borderWidth' => 2.5,
                    'pointRadius' => 4,
                    'pointHoverRadius' => 6,
                    'pointBackgroundColor' => '#6366f1',
                ],
            ],
            'labels' => $series['labels'],
        ];
    }

    // Displays the monthly statistics as a line chart.
    protected function getType(): string
    {
        return 'line';
    }
}
