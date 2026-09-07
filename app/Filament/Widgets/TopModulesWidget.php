<?php

namespace App\Filament\Widgets;

use App\Models\UsageEvent;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\DB;

class TopModulesWidget extends Widget
{
    protected static ?int $sort = 2;

    protected static string $view = 'filament.widgets.top-modules-widget';

    public function modules(): array
    {
        return UsageEvent::query()
            ->select('module', DB::raw('COUNT(*) as pageviews'), DB::raw('COUNT(DISTINCT tenant_id) as tenants'))
            ->where('panel', 'tenant')
            ->whereNotNull('module')
            ->where('created_at', '>=', now()->subDays(30))
            ->groupBy('module')
            ->orderByDesc('pageviews')
            ->limit(10)
            ->get()
            ->map(fn ($row) => [
                'module' => $row->module,
                'label' => $this->moduleLabel($row->module),
                'pageviews' => $row->pageviews,
                'tenants' => $row->tenants,
            ])
            ->all();
    }

    public function totalPageviews30d(): int
    {
        return UsageEvent::query()
            ->where('panel', 'tenant')
            ->where('created_at', '>=', now()->subDays(30))
            ->count();
    }

    private function moduleLabel(string $module): string
    {
        $labels = [
            'dashboard' => 'Dashboard',
            'plants' => 'Plantas',
            'indoors' => 'Indoors',
            'actions' => 'Acciones',
            'crop-plans' => 'Planes de cultivo',
            'seeds' => 'Semillas',
            'notifications' => 'Notificaciones',
            'analitycs' => 'Gráficos',
            'password-change' => 'Cambio de contraseña',
            'activation-pending' => 'Activación pendiente',
        ];

        return $labels[$module] ?? ucfirst(str_replace('-', ' ', $module));
    }
}
