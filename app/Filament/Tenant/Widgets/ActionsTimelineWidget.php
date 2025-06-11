<?php

namespace App\Filament\Tenant\Widgets;

use App\Models\Action;
use Filament\Widgets\ChartWidget;
use Carbon\Carbon;

class ActionsTimelineWidget extends ChartWidget
{
    protected static ?string $heading = 'Línea de Tiempo de Acciones';

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

        $actions = Action::with(['plants', 'action_type'])
            ->whereHas('plants', function($query) {
                $query->whereHas('indoor', function($q) {
                    $q->where('tenant_id', auth()->user()->tenant_id);
                });
            })
            ->whereBetween('action_date', [
                $startDate->startOfDay()->toDateTimeString(),
                $endDate->endOfDay()->toDateTimeString()
            ])
            ->orderBy('action_date')
            ->get()
            ->groupBy(function ($action) {
                return Carbon::parse($action->action_date)->format('Y-m-d');
            });

        $dates = collect(new \DatePeriod(
            $startDate->startOfDay(),
            new \DateInterval('P1D'),
            $endDate->endOfDay()
        ))->map(function ($date) {
            return $date->format('Y-m-d');
        });

        $data = $dates->mapWithKeys(function ($date) use ($actions) {
            return [$date => $actions->get($date, collect())->count()];
        });

        return [
            'datasets' => [
                [
                    'label' => 'Acciones por día',
                    'data' => $data->values(),
                    'backgroundColor' => '#36A2EB',
                    'borderColor' => '#36A2EB',
                    'fill' => false,
                ]
            ],
            'labels' => $dates->values(),
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }

    protected function getOptions(): array
    {
        return [
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'ticks' => [
                        'stepSize' => 1,
                    ],
                ],
            ],
            'aspectRatio' => 1.5,
        ];
    }
} 