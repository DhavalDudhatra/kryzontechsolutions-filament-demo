<?php

namespace App\Filament\Widgets\Concerns;

/**
 * Render the widget with the dashboard instead of a separate Livewire request.
 */
trait IsNotLazyLoaded
{
    public static function isLazyLoaded(): bool
    {
        return false;
    }
}
