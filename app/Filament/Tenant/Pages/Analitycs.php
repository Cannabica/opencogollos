<?php

namespace App\Filament\Tenant\Pages;

use Carbon\Carbon;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Toggle;
use App\Filament\Tenant\Widgets\ActionsChartWidget;
use App\Filament\Tenant\Widgets\ActionTypesPieWidget;
use App\Filament\Tenant\Widgets\ActionsTimelineWidget;
use App\Filament\Tenant\Widgets\PlantStatesByIndoorWidget;
use App\Filament\Tenant\Widgets\ActivityHeatmapWidget;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Pages\Page;

class Analitycs extends Page
{
    use InteractsWithForms;

    protected static string $view = 'filament.tenant.pages.analitycs';
    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';
    protected static ?string $navigationLabel = 'Graficos';
    protected static ?string $title = 'Graficos de cuidados';

    protected static bool $shouldRegisterNavigation = true;


    public function getSubheading(): ?string
    {
        return __('subheading_analitycs');
    }

    protected function getHeaderWidgets(): array
    {
        return [
            ActionsChartWidget::class,
            ActionTypesPieWidget::class,
            PlantStatesByIndoorWidget::class,
            ActivityHeatmapWidget::class,
            ActionsTimelineWidget::class,
        ];
    }

    protected function getColumns(): int|array
    {
        // Forzar una sola columna para todos los dispositivos
        return 1;
    }

    protected function isMobileDevice(): bool
    {
        return preg_match('/(android|bb\d+|meego).+mobile|avantgo|bada\/|blackberry|blazer|compal|elaine|fennec|hiptop|iemobile|ip(hone|od)|iris|kindle|lge |maemo|midp|mmp|mobile.+firefox|netfront|opera m(ob|in)i|palm( os)?|phone|p(ixi|re)\/|plucker|pocket|psp|series(4|6)0|symbian|treo|up\.(browser|link)|vodafone|wap|windows ce|xda|xiino/i', $_SERVER['HTTP_USER_AGENT']);
    }
}