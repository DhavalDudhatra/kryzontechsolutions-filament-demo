<?php

namespace App\Filament\Widgets\Concerns;

use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
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
     * @param  Builder<TModel>  $query
     * @return Collection<string, int>
     */
    protected function monthlyCounts(Builder $query): Collection
    {
        return $query->selectRaw('YEAR(created_at) as year, MONTH(created_at) as month, COUNT(*) as total')
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
