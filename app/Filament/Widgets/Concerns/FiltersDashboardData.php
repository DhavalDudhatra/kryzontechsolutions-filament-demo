<?php

namespace App\Filament\Widgets\Concerns;

use App\Models\Product;
use App\Models\User;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Throwable;

/** @template TModel of \Illuminate\Database\Eloquent\Model */
trait FiltersDashboardData
{
    protected function parseFilterDate(mixed $value): ?CarbonInterface
    {
        if ($value instanceof CarbonInterface) {
            return $value;
        }

        if (is_string($value) && filled($value)) {
            try {
                return Carbon::parse($value);
            } catch (Throwable) {
                return null;
            }
        }

        return null;
    }

    /**
     * @return array{0: CarbonInterface, 1: CarbonInterface}
     */
    protected function resolveDateRange(mixed $start, mixed $end,
        ?CarbonInterface $defaultStart = null,
        ?CarbonInterface $defaultEnd = null): array
    {
        $start = $this->parseFilterDate($start) ?? $defaultStart ?? now()->startOfYear();
        $end = $this->parseFilterDate($end) ?? $defaultEnd ?? now();

        if ($end->lt($start)) {
            [$start, $end] = [$end, $start];
        }

        return [$start->copy()->startOfDay(), $end->copy()->endOfDay()];
    }

    /**
     * Single cached snapshot for all dashboard widgets (one cache lookup, 4 aggregate queries).
     *
     * @return array{new_users: int, total_products: int, new_products: int, active: int, inactive: int, monthly: Collection<string, int>}
     */
    protected function dashboardSnapshot(CarbonInterface $start, CarbonInterface $end): array
    {
        // File store avoids a DB cache-table query on every dashboard load.
        return Cache::store('file')->remember(
            'dashboard_snapshot:'.$start->toDateString().':'.$end->toDateString(),
            60,
            function () use ($start, $end): array {
                $productCounts = Product::query()
                    ->selectRaw('COUNT(*) as total_products')
                    ->selectRaw('SUM(CASE WHEN created_at BETWEEN ? AND ? THEN 1 ELSE 0 END) as new_products', [$start, $end])
                    ->selectRaw('SUM(CASE WHEN created_at BETWEEN ? AND ? AND is_active = ? THEN 1 ELSE 0 END) as active_count', [$start, $end, true])
                    ->selectRaw('SUM(CASE WHEN created_at BETWEEN ? AND ? AND is_active = ? THEN 1 ELSE 0 END) as inactive_count', [$start, $end, false])
                    ->first();

                return [
                    'new_users' => User::query()->whereBetween('created_at', [$start, $end])->count(),
                    'total_products' => (int) ($productCounts?->total_products ?? 0),
                    'new_products' => (int) ($productCounts?->new_products ?? 0),
                    'active' => (int) ($productCounts?->active_count ?? 0),
                    'inactive' => (int) ($productCounts?->inactive_count ?? 0),
                    'monthly' => $this->monthlyCounts(Product::query()->whereBetween('created_at', [$start, $end])),
                ];
            },
        );
    }

    /**
     * @param  Builder<TModel>  $query
     * @return Collection<string, int>
     */
    protected function monthlyCounts(Builder $query): Collection
    {
        $driver = $query->getConnection()->getDriverName();
        $select = $driver === 'sqlite'
            ? "strftime('%Y', created_at) as year, strftime('%m', created_at) as month, COUNT(*) as total"
            : 'YEAR(created_at) as year, MONTH(created_at) as month, COUNT(*) as total';

        return $query->selectRaw($select)
            ->groupBy('year', 'month')
            ->get()
            ->mapWithKeys(fn ($row): array => [
                sprintf(
                    '%04d-%02d',
                    (int) $row->getAttribute('year'),
                    (int) $row->getAttribute('month'),
                ) => (int) $row->getAttribute('total'),
            ]);
    }

    /**
     * @param  Collection<string, int>  $counts
     * @return array{labels: list<string>, data: list<int>}
     */
    protected function monthlySeries(Collection $counts, CarbonInterface $start, CarbonInterface $end): array
    {
        $withYear = ! $start->isSameYear($end);
        $current = $start->copy()->startOfMonth();
        $labels = [];
        $data = [];
        $countByMonth = $counts->all();

        while ($current->lte($end)) {
            $labels[] = $current->format($withYear ? 'M y' : 'M');
            $data[] = (int) ($countByMonth[$current->format('Y-m')] ?? 0);
            $current = $current->addMonth();
        }

        return ['labels' => $labels, 'data' => $data];
    }
}
