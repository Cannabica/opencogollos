<?php

namespace App\Filament\Tenant\Widgets;

use Filament\Widgets\ChartWidget;
use Flowframe\Trend\Trend;
use App\Models\Action;
use App\Models\ActionType;
use Flowframe\Trend\TrendValue;
use Carbon\Carbon;

class ActionsChartWidget extends ChartWidget
{
    protected static ?string $heading = 'Evolución de cuidados';
    protected array $months;

    protected int | string | array $columnSpan = 2;

    protected function getData(): array
    {
        $start = now()->subMonths(3)->startOfMonth();
        $end = now()->endOfMonth();

        // Obtener todos los meses en el rango
        $this->months = [];
        $currentMonth = $start->copy();
        while ($currentMonth <= $end) {
            $this->months[] = $currentMonth->format('Y-m');
            $months[] = $currentMonth->format('Y-m');
            $currentMonth->addMonth();
        }


        // Obtener datos agrupados
        $trendData = Action::whereBetween('action_date', [$start, $end])
            ->selectRaw('
                action_type_id,
                strftime("%Y-%m", action_date) as month,
                COUNT(*) as aggregate
            ')
            ->groupBy('action_type_id', 'month')
            ->orderBy('month')
            ->get()
            ->groupBy('action_type_id');

        $actionTypes = ActionType::orderBy('id')->get();

        $datasets = [];
        $colors = $this->generateColorPalette($actionTypes->count());
        $colorIndex = 0;

        foreach ($actionTypes as $actionType) {
            \Log::debug("Procesando ActionType ID: {$actionType->id}", [
                'name' => $actionType->name,
                'total_actions' => Action::where('action_type_id', $actionType->id)->count()
            ]);

            // '#FF9F40', // Naranja
            // '#FFCD56', // Amarillo
            // '#36A2EB', // Azul

            $dataset = [
                'label' => __(str_replace('Registrar ', '', $actionType->name)),
                'data' => array_fill(0, count($months), 0),
                'borderColor' => $colors[$colorIndex],
                'backgroundColor' => $colors[$colorIndex],
                'pointBackgroundColor' => $colors[$colorIndex],
                'pointBorderColor' => '#ffffff',
                'pointBorderWidth' => 2,
                'pointRadius' => 5,
                'pointHoverRadius' => 8,
                'tension' => 0.4,
                'fill' => false,
                'cubicInterpolationMode' => 'monotone',
                'spanGaps' => true,
            ];

            $trendValues = $trendData->where('action_type_id', $actionType->id);

            \Log::debug("Trend values para {$actionType->name}", [
                'count_trend_values' => $trendValues->count(),
                'first_value' => $trendValues->first()?->toArray()
            ]);

            \Log::debug('Datos crudos de Trend', [
                'data' => $trendData->toArray(),
                'months_generated' => $months
            ]);


            // Mapear datos existentes
            foreach ($trendData->get($actionType->id, []) as $trendValue) {
                $monthString = $trendValue->month;

                // Agregar debug para verificar tipos
                \Log::debug('Tipos de datos', [
                    'trend_month_type' => gettype($trendValue->month),
                    'months_array_type' => array_map('gettype', $months)
                ]);

                $monthIndex = array_search(
                    $trendValue->month,
                    $months
                );

                if ($monthIndex !== false) {
                    $dataset['data'][$monthIndex] = $trendValue->aggregate;
                }

                \Log::debug("Mapeando valor", [
                    'trend_date' => $trendValue->month,
                    'month_string' => $monthString,
                    'month_index' => $monthIndex,
                    'aggregate' => $trendValue->aggregate
                ]);


                if ($monthIndex !== false) {
                    $dataset['data'][$monthIndex] = $trendValue->aggregate;
                } else {
                    \Log::warning("Mes no encontrado en el array de meses", [
                        'expected_months' => $months,
                        'current_month' => $monthString
                    ]);
                }
            }

            $datasets[] = $dataset;
            $colorIndex++;
        }

        // dd($datasets);

        return [
            'datasets' => $datasets,
            'labels' => $this->getMonthLabels()
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
            'maintainAspectRatio' => true,
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
                        'stepSize' => 1,
                        'precision' => 0,
                    ]
                ],
                'x' => [
                    'type' => 'category',
                    'labels' => $this->getMonthLabels(),
                    'title' => [
                        'display' => true,
                        'text' => __('Periodo')
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

    private function getMonthLabels(): array
    {
        return array_map(
            fn($month) => Carbon::createFromFormat('Y-m', $month)
                ->translatedFormat('M Y'),
            $this->months
        );
    }

    private function generateColorPalette(int $count): array
    {
        return [
            '#4BC0C0', // Turquesa
            '#9966FF', // Lavanda
            '#FF9F40', // Naranja
            '#FFCD56', // Amarillo
            '#36A2EB', // Azul
            '#FF6384', // Rosa
            '#4D5360'  // Gris oscuro
        ];
    }
}