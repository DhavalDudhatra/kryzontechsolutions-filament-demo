<?php

namespace App\Filament\Widgets;

use App\Enums\Permission;
use App\Filament\Widgets\Concerns\FiltersDashboardData;
use App\Filament\Widgets\Concerns\IsNotLazyLoaded;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Illuminate\Support\Facades\Gate;

class ProductsStatusPieChart extends ChartWidget
{
    /** @use FiltersDashboardData<Product> */
    use FiltersDashboardData;

    use InteractsWithPageFilters;
    use IsNotLazyLoaded;

    protected ?string $heading = 'Product status';

    protected ?string $description = 'Products added in the selected period, grouped by active status.';

    protected static ?int $sort = 3;

    protected int|string|array $columnSpan = 1;

    protected ?string $maxHeight = '300px';

    // Only users with the chart permission see this widget.
    public static function canView(): bool
    {
        return Gate::allows(Permission::WidgetsProductsChart->value);
    }

    protected function getData(): array
    {
        [$start, $end] = $this->resolveDateRange(
            $this->pageFilters['startDate'] ?? null,
            $this->pageFilters['endDate'] ?? null,
        );

        $snapshot = $this->dashboardSnapshot($start, $end);
        $active = $snapshot['active'];
        $inactive = $snapshot['inactive'];

        if ($active + $inactive === 0) {
            return $this->pie([''], [1], ['rgba(148, 163, 184, 0.35)']);
        }

        return $this->pie(['Active', 'Inactive'], [$active, $inactive], ['#10b981', '#ef4444']);
    }

    protected function getType(): string
    {
        return 'pie';
    }

    /** Builds the chart data with a white border between slices. */
    private function pie(array $labels, array $values, array $colors): array
    {
        return [
            'datasets' => [
                [
                    'data' => $values,
                    'backgroundColor' => $colors,
                    'borderColor' => '#ffffff',
                    'borderWidth' => 2,
                ],
            ],
            'labels' => $labels,
        ];
    }
}
