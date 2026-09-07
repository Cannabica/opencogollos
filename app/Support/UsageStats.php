<?php

namespace App\Support;

use App\Models\Action;
use App\Models\Scopes\TenantScope;
use App\Models\Tenant;
use App\Models\UsageEvent;
use Illuminate\Support\Facades\DB;

/**
 * Queries agregadas de telemetría y uso para el panel SuperAdmin.
 * Se usa desde la vista de la página (server-side puro, sin depender
 * del montaje de widgets por Livewire).
 */
class UsageStats
{
    public static function summary(): array
    {
        $since7d = now()->subDays(7);

        return [
            'pageviews7d' => UsageEvent::query()->where('created_at', '>=', $since7d)->count(),
            'users7d' => UsageEvent::query()->where('created_at', '>=', $since7d)->distinct()->count('user_id'),
            'tenants7d' => UsageEvent::query()
                ->where('panel', 'tenant')
                ->where('created_at', '>=', $since7d)
                ->distinct()
                ->count('tenant_id'),
            'actions7d' => Action::query()->where('action_date', '>=', $since7d)->count(),
        ];
    }

    public static function funnel(): array
    {
        $registered = Tenant::query()->count();

        // Vista administrativa: ignora el TenantScope de Indoor/Plant para
        // contar el ciclo real de cada tenant (el scope filtra por el tenant
        // del usuario autenticado y rompería el conteo para el superadmin).
        $noTenantScope = fn ($q) => $q->withoutGlobalScope(TenantScope::class);

        $steps = [
            ['label' => 'Tenants registrados', 'count' => $registered],
            ['label' => 'Crearon un indoor', 'count' => Tenant::query()->whereHas('indoors', $noTenantScope)->count()],
            ['label' => 'Registraron plantas', 'count' => Tenant::query()->whereHas('indoors', fn ($q) => $q
                ->withoutGlobalScope(TenantScope::class)
                ->whereHas('plants', $noTenantScope)
            )->count()],
            ['label' => 'Cargaron acciones', 'count' => Tenant::query()->whereHas('actions')->count()],
        ];

        return array_map(static function (array $step) use ($registered) {
            $step['pct'] = $registered > 0 ? (int) round(($step['count'] / $registered) * 100) : 0;

            return $step;
        }, $steps);
    }

    public static function topModules(int $days = 30): array
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

        return UsageEvent::query()
            ->select('module', DB::raw('COUNT(*) as pageviews'), DB::raw('COUNT(DISTINCT tenant_id) as tenants'))
            ->where('panel', 'tenant')
            ->whereNotNull('module')
            ->where('created_at', '>=', now()->subDays($days))
            ->groupBy('module')
            ->orderByDesc('pageviews')
            ->limit(10)
            ->get()
            ->map(static fn ($row) => [
                'module' => $row->module,
                'label' => $labels[$row->module] ?? ucfirst(str_replace('-', ' ', $row->module)),
                'pageviews' => (int) $row->pageviews,
                'tenants' => (int) $row->tenants,
            ])
            ->all();
    }

    public static function totalTenantPageviews(int $days = 30): int
    {
        return UsageEvent::query()
            ->where('panel', 'tenant')
            ->where('created_at', '>=', now()->subDays($days))
            ->count();
    }

    /**
     * Matriz de actividad 24h x 7días para el heatmap.
     *
     * @return array{labels: string[], hours: int[], data: array<int, array<int, int>>}
     */
    public static function activityMatrix(int $days = 30): array
    {
        $driver = DB::connection()->getDriverName();
        if ($driver === 'sqlite') {
            $dayExpr = "strftime('%w', created_at)";
            $hourExpr = "strftime('%H', created_at)";
        } else {
            $dayExpr = "EXTRACT(DOW FROM created_at)";
            $hourExpr = "EXTRACT(HOUR FROM created_at)";
        }

        $activities = UsageEvent::query()
            ->select(
                DB::raw("{$dayExpr} as day_of_week"),
                DB::raw("{$hourExpr} as hour"),
                DB::raw('COUNT(*) as total')
            )
            ->where('panel', 'tenant')
            ->where('created_at', '>=', now()->subDays($days))
            ->groupBy('day_of_week', 'hour')
            ->get();

        $data = array_fill(0, 24, array_fill(0, 7, 0));
        foreach ($activities as $activity) {
            $dayIndex = (int) $activity->day_of_week;
            $hour = (int) $activity->hour;
            if ($dayIndex >= 0 && $dayIndex <= 6 && $hour >= 0 && $hour <= 23) {
                $data[$hour][$dayIndex] = (int) $activity->total;
            }
        }

        return [
            'labels' => ['Domingo', 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado'],
            'hours' => range(0, 23),
            'data' => $data,
        ];
    }

    public static function heatColor(int $value): string
    {
        return match (true) {
            $value === 0 => 'rgba(240, 240, 240, 0.5)',
            $value <= 5 => 'rgba(103, 169, 207, 0.8)',
            $value <= 15 => 'rgba(209, 229, 240, 0.8)',
            $value <= 40 => 'rgba(253, 219, 199, 0.8)',
            $value <= 100 => 'rgba(239, 138, 98, 0.8)',
            default => 'rgba(178, 24, 43, 0.8)',
        };
    }
}
