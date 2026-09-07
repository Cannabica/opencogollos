<?php

namespace App\Filament\Widgets;

use App\Models\Tenant;
use Filament\Widgets\Widget;

class ActivationFunnelWidget extends Widget
{
    protected static ?int $sort = 1;

    protected static string $view = 'filament.widgets.activation-funnel-widget';

    public function funnel(): array
    {
        $registered = Tenant::query()->count();

        $steps = [
            ['label' => 'Tenants registrados', 'count' => $registered],
            ['label' => 'Crearon un indoor', 'count' => Tenant::query()->whereHas('indoors')->count()],
            ['label' => 'Registraron plantas', 'count' => Tenant::query()->whereHas('indoors.plants')->count()],
            ['label' => 'Cargaron acciones', 'count' => Tenant::query()->whereHas('actions')->count()],
        ];

        return array_map(function (array $step) use ($registered) {
            $step['pct'] = $registered > 0 ? round(($step['count'] / $registered) * 100) : 0;

            return $step;
        }, $steps);
    }
}
