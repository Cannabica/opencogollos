<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;

class UsageAnalytics extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';

    protected static ?string $navigationLabel = 'Uso de la plataforma';

    protected static ?string $title = 'Uso de la plataforma';

    protected static ?int $navigationSort = 3;

    protected static string $view = 'filament.pages.usage-analytics';
}
