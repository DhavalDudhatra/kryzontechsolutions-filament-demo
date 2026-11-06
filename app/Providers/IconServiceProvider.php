<?php

namespace App\Providers;

use App\Enums\CustomIcon;
use Filament\Support\Facades\FilamentIcon;
use Illuminate\Support\HtmlString;
use Illuminate\Support\ServiceProvider;

final class IconServiceProvider extends ServiceProvider
{
    /**
     * Register icon aliases inside the Filament subsystem.
     */
    public function boot(): void
    {
        $registry = collect(CustomIcon::cases())
            ->mapWithKeys(fn (CustomIcon $icon) => [
                $icon->value => new HtmlString($icon->svg()),
            ])
            ->toArray();

        FilamentIcon::register($registry);
    }
}
