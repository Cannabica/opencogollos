<?php

namespace App\Filament\Widgets;

use App\Models\UsageEvent;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class UsageActivityHeatmapWidget extends ChartWidget
{
    protected static ?int $sort = 3;

    protected static ?string $heading = 'Actividad de la plataforma (últimos 30 días)';

    protected function getData(): array
    {
        $startDate = now()->subDays(30)->startOfDay();

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
            ->where('created_at', '>=', $startDate)
            ->groupBy('day_of_week', 'hour')
            ->get();

        $data = array_fill(0, 24, array_fill(0, 7, 0));

        foreach ($activities as $activity) {
            $dayIndex = (int) $activity->day_of_week;
            $hour = (int) $activity->hour;
            if ($dayIndex >= 0 && $dayIndex <= 6 && $hour >= 0 && $hour <= 23) {
                $data[$hour][$dayIndex] = $activity->total;
            }
        }

        $dayNames = ['Domingo', 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado'];

        $datasets = [];
        foreach ($data as $hour => $days) {
            $datasets[] = [
                'label' => sprintf('%02d:00', $hour),
                'data' => array_values($days),
                'backgroundColor' => $this->colorForValue(max($days)),
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

    private function colorForValue(int $value): string
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

    protected function getOptions(): array
    {
        return [
            'responsive' => true,
            'maintainAspectRatio' => false,
            'scales' => [
                'x' => [
                    'stacked' => true,
                    'grid' => ['display' => false],
                    'title' => ['display' => true, 'text' => 'Días de la semana'],
                ],
                'y' => [
                    'stacked' => true,
                    'grid' => ['display' => false],
                    'title' => ['display' => true, 'text' => 'Horas del día'],
                ],
            ],
            'plugins' => [
                'legend' => ['display' => false],
                'tooltip' => [
                    'callbacks' => [
                        'title' => "function(context) { return context[0].label + ' - ' + context[0].dataset.label; }",
                        'label' => "function(context) { return 'Páginas: ' + context.raw; }",
                    ],
                ],
            ],
        ];
    }
}
