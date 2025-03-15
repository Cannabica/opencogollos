<?php

namespace App\Filament\Tenant\Widgets;

use App\Models\Action;
use Filament\Widgets\ChartWidget;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use App\Models\ActionType;

class ActionTypesPieWidget extends ChartWidget
{
    protected static ?string $heading = 'Distribución de Tipos de Acciones';

    public ?string $filter = '';

    protected function getFilters(): ?array
    {
        return [
            'all' => 'Todos',
            'last30days' => 'Últimos 30 días',
            'last7days' => 'Últimos 7 días',
            'today' => 'Hoy',
        ];
    }

    protected function getData(): array
    {
        $startDate = match ($this->filter) {
            'last30days' => now()->subDays(30),
            'last7days' => now()->subDays(7),
            'today' => now()->startOfDay(),
            default => now()->subDays(30),
        };

        $endDate = now();

        // Obtener las acciones agrupadas por tipo
        $actions = Action::select('action_type_id', DB::raw('COUNT(*) as count'))
            ->whereBetween('action_date', [
                $startDate->startOfDay()->toDateTimeString(),
                $endDate->endOfDay()->toDateTimeString()
            ])
            ->groupBy('action_type_id')
            ->get();

        // Obtener los nombres de los tipos de acción
        $actionTypes = ActionType::whereIn('id', $actions->pluck('action_type_id'))->get()->keyBy('id');

        $colors = [
            1 => '#36A2EB',  // Riego
            2 => '#FF6384',  // Poda
            3 => '#FFCE56',  // Aplique producto
            4 => '#4BC0C0',  // Transplante
            5 => '#9966FF',  // Observación con foto
            6 => '#FF9F40',  // Muerte de la planta
            7 => '#C9CBCF'   // Cambio de estado
        ];

        return [
            'datasets' => [
                [
                    'data' => $actions->pluck('count'),
                    'backgroundColor' => $actions->pluck('action_type_id')->map(function ($typeId) use ($colors) {
                        return $colors[$typeId] ?? '#' . substr(md5($typeId), 0, 6);
                    }),
                ],
            ],
            'labels' => $actions->pluck('action_type_id')->map(function ($typeId) use ($actionTypes) {
                return $actionTypes[$typeId]->name ?? 'Desconocido';
            }),
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => [
                    'position' => 'bottom',
                ],
            ],
        ];
    }
} 