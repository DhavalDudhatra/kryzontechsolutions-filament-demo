<?php

namespace App\Providers;

use App\Filament\Support\RemoveFilamentQueryStrings;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

class FilamentServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        // Registered after Livewire's own dehydrate listeners so the URL effects exist first.
        Livewire::listen('dehydrate', new RemoveFilamentQueryStrings);
    }
}
