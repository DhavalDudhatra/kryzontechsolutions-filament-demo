<?php

namespace App\Filament\Widgets;

use App\Enums\Permission;
use App\Filament\Widgets\Concerns\FiltersDashboardData;
use App\Filament\Widgets\Concerns\IsNotLazyLoaded;
use App\Models\Product;
use App\Models\User;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;

class StatsOverview extends StatsOverviewWidget
{
    /** @use FiltersDashboardData<Model> */
    use FiltersDashboardData;

    use InteractsWithPageFilters;
    use IsNotLazyLoaded;

    protected static ?int $sort = 1;

    // Only users who can view products see these stats.
    public static function canView(): bool
    {
        return Gate::allows(Permission::ProductsView->value);
    }

    protected function getStats(): array
    {
        // Date range chosen in the dashboard filters.
        [$start, $end] = $this->resolveDateRange(
            $this->pageFilters['startDate'] ?? null,
            $this->pageFilters['endDate'] ?? null,
        );

        $newUsers = User::query()->whereBetween('created_at', [$start, $end])->count();
        $productCounts = Product::query()
            ->selectRaw('COUNT(*) as total_products')
            ->selectRaw(
                'SUM(CASE WHEN created_at BETWEEN ? AND ? THEN 1 ELSE 0 END) as new_products',
                [$start, $end],
            )
            ->first();
        $newProducts = (int) ($productCounts?->new_products ?? 0);
        $totalProducts = (int) ($productCounts?->total_products ?? 0);

        $periodDescription = filled($this->pageFilters['startDate'] ?? null) || filled($this->pageFilters['endDate'] ?? null)
            ? 'during the selected date range'
            : 'year to date';

        return [
            Stat::make('Total Products', $totalProducts)
                ->description('Current catalogue size')
                ->icon(Heroicon::OutlinedCube)
                ->color('primary'),

            Stat::make('New Users', $newUsers)
                ->description("Registered {$periodDescription}")
                ->icon(Heroicon::OutlinedUser)
                ->color('info'),

            Stat::make('New Products', $newProducts)
                ->description("Added {$periodDescription}")
                ->icon(Heroicon::OutlinedSparkles)
                ->color('success'),
        ];
    }
}
