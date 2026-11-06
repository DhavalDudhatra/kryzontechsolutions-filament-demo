import * as React from "react";
import { Input } from "./input";
import { Calendar } from "lucide-react";

export interface DateRangeFilterProps {
    /** Label for the start date input */
    startLabel?: string;
    /** Label for the end date input */
    endLabel?: string;
    /** Placeholder for start date */
    startPlaceholder?: string;
    /** Placeholder for end date */
    endPlaceholder?: string;
    /** Initial start date */
    startDate?: Date;
    /** Initial end date */
    endDate?: Date;
    /** On start date change */
    onStartChange: (date: Date | null) => void;
    /** On end date change */
    onEndChange: (date: Date | null) => void;
    /** Whether the inputs are disabled */
    disabled?: boolean;
    /** Custom className for the container */
    className?: string;
}

/**
 * Professional Date Range Filter component with calendar icons
 * Used for filtering data by date ranges in admin panels
 */
export function DateRangeFilter({
    startLabel = "Start Date",
    endLabel = "End Date",
    startPlaceholder = "Select start date",
    endPlaceholder = "Select end date",
    startDate,
    endDate,
    onStartChange,
    onEndChange,
    disabled = false,
    className = "",
}: DateRangeFilterProps) {
    return (
        <div className={className} spacing={4} role="group" aria-label="Date range filter">
            <div className="space-y-2">
                {/* Start Date Input */}
                <div className="relative">
                    <label className="absolute left-0 top-1/2 -translate-y-1/2 text-sm text-gray-500 dark:text-gray-400 pointer-events-none">
                        {startLabel}
                    </label>
                    <Input
                        type="date"
                        value={startDate?.toISOString().split('T')[0] || ""}
                        onChange={(e: React.ChangeEvent<HTMLInputElement>) => {
                            const date = e.target.valueAsDate;
                            onStartChange(date);
                        }}
                        placeholder={startPlaceholder}
                        disabled={disabled}
                        className="pr-10"
                        autoComplete="off"
                    />
                    {/* Calendar icon */}
                    <span className="absolute right-2 top-1/2 -translate-y-1/2 text-gray-400 dark:text-gray-500">
                        <Calendar className="h-4 w-4" />
                    </span>
                </div>

                {/* End Date Input */}
                <div className="relative">
                    <label className="absolute left-0 top-1/2 -translate-y-1/2 text-sm text-gray-500 dark:text-gray-400 pointer-events-none">
                        {endLabel}
                    </label>
                    <Input
                        type="date"
                        value={endDate?.toISOString().split('T')[0] || ""}
                        onChange={(e: React.ChangeEvent<HTMLInputElement>) => {
                            const date = e.target.valueAsDate;
                            onEndChange(date);
                        }}
                        placeholder={endPlaceholder}
                        disabled={disabled}
                        className="pr-10"
                        autoComplete="off"
                    />
                    {/* Calendar icon */}
                    <span className="absolute right-2 top-1/2 -translate-y-1/2 text-gray-400 dark:text-gray-500">
                        <Calendar className="h-4 w-4" />
                    </span>
                </div>
            </div>
        </div>
    );
}