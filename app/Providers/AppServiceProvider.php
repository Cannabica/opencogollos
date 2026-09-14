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

        // WS4 · T4.6 / hallazgo H1 (resuelto en T9.5): el manifest dinámico de la PWA
        // se declara en el head de los paneles Filament, que son las páginas que SÍ se
        // renderizan. Antes el único `rel="manifest"` vivía en resources/views/
        // welcome.blade.php, una vista muerta (0 referencias; `/` redirige a
        // /tenant/login), así que ningún navegador descubría el manifest.
        FilamentView::registerRenderHook(
            PanelsRenderHook::HEAD_END,
            fn () => view('filament.manifest-link'),
        );

    }
}
