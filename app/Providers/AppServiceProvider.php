<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Filament\Facades\Filament;
use Filament\View\PanelsRenderHook;
use Illuminate\Support\Facades\Blade;
use Filament\Support\Facades\FilamentView;
use App\Filament\Tenant\Widgets\ProductNotificationsWidget;
use Livewire\Livewire;


class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Livewire::component('product-notifications-widget', ProductNotificationsWidget::class);
        
        FilamentView::registerRenderHook(
            PanelsRenderHook::SIDEBAR_NAV_START,
            fn (): string => Blade::render('<livewire:ActionShortcuts />'),
        );
        FilamentView::registerrenderhook(
            PanelsRenderHook::HEAD_END,
            fn () => view('analyticsTag'),        
        );

    }
}
