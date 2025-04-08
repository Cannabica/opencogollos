<?php

namespace App\Filament\Tenant\Widgets;

use Filament\Widgets\ChartWidget;
use App\Models\Action;
use App\Models\ActionType;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use function Filament\tenant;

class ActionsChartWidget extends ChartWidget
{
    protected static ?string $heading = 'Evolución de cuidados';

    protected int | string | array $columnSpan = 1;

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

        // Obtener todos los tipos de acciones
        $actionTypes = ActionType::all();

        // Preparar el rango de fechas
        $dates = collect(new \DatePeriod(
            $startDate->startOfDay(),
            new \DateInterval('P1D'),
            $endDate->endOfDay()
        ))->map(function ($date) {
            return $date->format('Y-m-d');
        });

        // Obtener las acciones agrupadas por tipo y fecha
        $actions = Action::query()
            ->whereHas('plants', function($query) {
                $query->whereHas('indoor', function($q) {
                    $q->where('tenant_id', auth()->user()->tenant_id);
                });
            })
            ->select(
                'action_type_id',
                DB::raw('DATE(action_date) as date'),
                DB::raw('COUNT(*) as count')
            )
            ->whereBetween('action_date', [
                $startDate->startOfDay(),
                $endDate->endOfDay()
            ])
            ->groupBy('action_type_id', 'date')
            ->get();

        // Preparar los datos para el gráfico
        $datasets = [];
        $colors = [
            1 => '#36A2EB',  // Riego
            2 => '#FF6384',  // Poda
            3 => '#FFCE56',  // Aplique producto
            4 => '#4BC0C0',  // Transplante
            5 => '#9966FF',  // Observación con foto
            6 => '#FF9F40',  // Muerte de la planta
            7 => '#C9CBCF'   // Cambio de estado
        ];

        // Crear un dataset para cada tipo de acción
        foreach ($actionTypes as $type) {
            $typeData = $dates->mapWithKeys(function ($date) {
                return [$date => 0];
            })->toArray();

            // Llenar con los datos reales
            foreach ($actions as $action) {
                if ($action->action_type_id === $type->id) {
                    $typeData[$action->date] = $action->count;
                }
            }

            $datasets[] = [
                'label' => $type->name,
                'data' => array_values($typeData),
                'borderColor' => $colors[$type->id] ?? '#' . substr(md5($type->id), 0, 6),
                'backgroundColor' => $colors[$type->id] ?? '#' . substr(md5($type->id), 0, 6),
                'fill' => false,
                'tension' => 0.4,
            ];
        }

        return [
            'datasets' => $datasets,
            'labels' => $dates->map(function ($date) {
                return Carbon::parse($date)->translatedFormat('d M');
            })->values(),
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }

    protected function getOptions(): array
    {
        return [
            'responsive' => true,
            'maintainAspectRatio' => false,
            'interaction' => [
                'mode' => 'nearest',
                'intersect' => false
            ],
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'title' => [
                        'display' => true,
                        'text' => __('Cantidad de acciones'),
                        'color' => '#666'
                    ],
                    'grid' => [
                        'color' => 'rgba(200, 200, 200, 0.3)'
                    ],
                    'ticks' => [
                        'color' => '#999',
                        'stepSize' => 3,
                        'precision' => 0,
                    ]
                ],
                'x' => [
                    'title' => [
                        'display' => true,
                        'text' => __('Fecha')
                    ],
                    'grid' => [
                        'display' => true
                    ]
                ]
            ],
            'plugins' => [
                'legend' => [
                    'position' => 'top',
                    'labels' => [
                        'color' => '#666',
                        'boxWidth' => 15,
                        'padding' => 20,
                        'usePointStyle' => true
                    ]
                ],
                'tooltip' => [
                    'backgroundColor' => 'rgba(0, 0, 0, 0.8)',
                    'titleColor' => '#fff',
                    'bodyColor' => '#fff',
                    'cornerRadius' => 4,
                    'displayColors' => true,
                    'mode' => 'index',
                    'intersect' => false
                ]
            ]
        ];
    }
}