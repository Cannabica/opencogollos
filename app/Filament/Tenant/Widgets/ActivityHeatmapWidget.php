<?php

namespace App\Filament\Tenant\Widgets;

use App\Models\Action;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Toggle;

class ActivityHeatmapWidget extends ChartWidget
{
    protected static ?string $heading = 'Mapa de Calor de seguimientos';

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

        $query = Action::select(
            DB::raw("strftime('%w', action_date) as day_of_week"),
            DB::raw("strftime('%H', action_date) as hour"),
            DB::raw('COUNT(*) as total')
        )
        ->whereHas('plants', function($query) {
            $query->whereHas('indoor', function($q) {
                $q->where('tenant_id', auth()->user()->tenant_id);
            });
        })
        ->whereBetween('action_date', [
            $startDate->startOfDay()->toDateTimeString(),
            $endDate->endOfDay()->toDateTimeString()
        ]);

        $activities = $query->groupBy('day_of_week', 'hour')->get();

        // Inicializar matriz de datos
        $data = array_fill(0, 24, array_fill(0, 7, 0));

        // Llenar la matriz con los datos
        foreach ($activities as $activity) {
            $dayIndex = (int)$activity->day_of_week;
            $hour = (int)$activity->hour;
            $data[$hour][$dayIndex] = $activity->total;
        }

        $dayNames = ['Domingo', 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado'];

        // Crear datasets para cada hora
        $datasets = [];
        foreach ($data as $hour => $days) {
            $datasets[] = [
                'label' => sprintf('%02d:00', $hour),
                'data' => array_values($days),
                'backgroundColor' => $this->getColorForValue(max($days)),
                'borderColor' => 'rgba(255, 255, 255, 0.5)',
                'borderWidth' => 1,
                'barPercentage' => 1.0,
                'categoryPercentage' => 1.0,
            ];
        }

        return [
            'datasets' => $datasets,
            'labels' => $dayNames,
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }

    private function getColorForValue($value): string
    {
        // Escala de colores desde azul claro hasta rojo intenso
        if ($value == 0) return 'rgba(240, 240, 240, 0.5)';
        if ($value <= 2) return 'rgba(103, 169, 207, 0.8)';
        if ($value <= 4) return 'rgba(209, 229, 240, 0.8)';
        if ($value <= 6) return 'rgba(253, 219, 199, 0.8)';
        if ($value <= 8) return 'rgba(239, 138, 98, 0.8)';
        return 'rgba(178, 24, 43, 0.8)';
    }

    protected function getOptions(): array
    {
        return [
            'responsive' => true,
            'maintainAspectRatio' => false,
            'scales' => [
                'x' => [
                    'stacked' => true,
                    'grid' => [
                        'display' => false,
                    ],
                    'title' => [
                        'display' => true,
                        'text' => 'Días de la semana',
                    ],
                ],
                'y' => [
                    'stacked' => true,
                    'grid' => [
                        'display' => false,
                    ],
                    'title' => [
                        'display' => true,
                        'text' => 'Horas del día',
                    ],
                ],
            ],
            'plugins' => [
                'legend' => [
                    'display' => false,
                ],
                'tooltip' => [
                    'callbacks' => [
                        'title' => "function(context) {
                            return context[0].label + ' - ' + context[0].dataset.label;
                        }",
                        'label' => "function(context) {
                            return 'Acciones: ' + context.raw;
                        }",
                    ],
                ],
            ],
        ];
    }
}