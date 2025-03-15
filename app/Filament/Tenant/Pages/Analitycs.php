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


    public function getSubheading(): ?string
    {
        return __('subheading_analitycs');
    }
    protected function getHeaderWidgets(): array
    {
        return [
            ActionsChartWidget::class,
            ActivityHeatmapWidget::class,
            ActionsTimelineWidget::class,
            PlantStatesByIndoorWidget::class,
            ActionTypesPieWidget::class,
        ];
    }
}