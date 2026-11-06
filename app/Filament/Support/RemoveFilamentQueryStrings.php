<?php

namespace App\Filament\Support;

use Livewire\Component;
use Livewire\Mechanisms\HandleComponents\ComponentContext;

/**
 * Stops Filament components from writing their Livewire state to the query string.
 *
 * Livewire normally sends a "url" effect on mount for every #[Url] property
 * (tabs, filters, search, sort, grouping, pagination, actions...). The effect
 * only controls browser URL syncing; removing it keeps the component state and
 * its Livewire updates untouched.
 */
class RemoveFilamentQueryStrings
{
    public function __invoke(Component $component, ComponentContext $context): void
    {
        if (! $this->isFilamentComponent($component)) {
            return;
        }

        unset($context->effects['url']);
    }

    private function isFilamentComponent(Component $component): bool
    {
        foreach ([$component::class, ...class_parents($component)] as $class) {
            if (str_starts_with($class, 'Filament\\') || str_starts_with($class, 'App\\Filament\\')) {
                return true;
            }
        }

        return false;
    }
}
