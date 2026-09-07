<?php

namespace App\Filament\Widgets;

use App\Models\Action;
use App\Models\UsageEvent;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class UsageStatsOverview extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        $since7d = now()->subDays(7);

        $pageviews7d = UsageEvent::query()->where('created_at', '>=', $since7d)->count();
        $users7d = UsageEvent::query()->where('created_at', '>=', $since7d)->distinct()->count('user_id');
        $tenants7d = UsageEvent::query()
            ->where('panel', 'tenant')
            ->where('created_at', '>=', $since7d)
            ->distinct()
            ->count('tenant_id');
        $actions7d = Action::query()->where('action_date', '>=', $since7d)->count();

        return [
            Stat::make('Páginas vistas (7d)', $pageviews7d)
                ->description('En paneles tenant y superadmin')
                ->icon('heroicon-o-eye'),
            Stat::make('Usuarios con actividad (7d)', $users7d)
                ->description('Usuarios únicos que navegaron')
                ->icon('heroicon-o-user-group'),
            Stat::make('Tenants activos (7d)', $tenants7d)
                ->description('Cultivadores que usaron la plataforma')
                ->icon('heroicon-o-building-office-2'),
            Stat::make('Acciones de cultivo (7d)', $actions7d)
                ->description('Seguimientos registrados por los tenants')
                ->icon('heroicon-o-clipboard-document-list'),
        ];
    }
}
