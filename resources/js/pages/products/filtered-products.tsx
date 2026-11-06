import { DateRangeFilter } from "@/components/DateRangeFilter";
import { useState } from "react";

export default function ProductsWithFilter({ products, default_no_image }: any) {
    const [startDate, setStartDate] = useState<Date | null>(null);
    const [endDate, setEndDate] = useState<Date | null>(null);

    return (
        <div className="space-y-6">
            {/* Date Range Filter */}
            <DateRangeFilter
                startLabel="Filter From"
                endLabel="Filter To"
                startPlaceholder="Select start date"
                endPlaceholder="Select end date"
                startDate={startDate}
                endDate={endDate}
                onStartChange={setStartDate}
                onEndChange={setEndDate}
                disabled={false}
                className="border rounded-lg p-4 shadow-sm"
            />

            {/* Products grid - using existing products data */}
            {/* ... existing products grid code ... */}
            <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                {products.data.map((product: any) => (
                    <div
                        key={product.id}
                        className="group min-w-0 overflow-hidden rounded-2xl border border-slate-200 bg-white transition duration-200 hover:border-slate-300 hover:shadow-lg hover:shadow-slate-900/5 dark:border-zinc-800 dark:bg-zinc-900 dark:hover:border-zinc-700 dark:hover:shadow-black/20"
                    >
                        <div className="flex aspect-[4/3] items-center justify-center overflow-hidden bg-slate-100 dark:bg-zinc-800">
                            {/* Product image */}
                        </div>
                        <div className="p-5">
                            <p className="truncate text-xs font-medium text-blue-700 dark:text-blue-300">
                                {product.category}
                            </p>
                            <h3 className="mt-2 line-clamp-2 min-h-12 text-base font-semibold leading-6">
                                {product.name}
                            </h3>
                            <p className="mt-2 text-sm font-semibold tabular-nums text-slate-800 dark:text-zinc-100">
                                {product.price}
                            </p>
                        </div>
                    </div>
                ))}
            </div>
        </div>
    );
}