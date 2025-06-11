<?php

namespace App\Filament\Tenant\Widgets;

use App\Models\Action;
use App\Models\ActionType;
use Carbon\Carbon;
use Filament\Facades\Filament;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class ActionTypesLineWidget extends ChartWidget
{
    protected static ?string $heading = 'Acciones por tipo';
    protected int | string | array $columnSpan = 'full';

    public ?string $filter = 'last30days';

    protected function getFilters(): ?array
    {
        return [
            'last30days' => 'Últimos 30 días',
            'last7days' => 'Últimos 7 días',
            'today' => 'Hoy',
        ];
    }

    protected function getData(): array
    {
        $startDate = match ($this->filter) {
            'last7days' => now()->subDays(7),
            'today' => now()->startOfDay(),
            default => now()->subDays(30),
        };

        $endDate = now();

        $actionTypes = ActionType::all();
        $dates = collect(new \DatePeriod(
            $startDate->startOfDay(),
            new \DateInterval('P1D'),
            $endDate->endOfDay()
        ))->map(fn($date) => $date->format('Y-m-d'));

        $actions = Action::query()
            ->when(Filament::getTenant(), fn($query) => $query->where('tenant_id', Filament::getTenant()->id))
            ->whereBetween('action_date', [$startDate, $endDate])
            ->select(
                'action_type_id',
                DB::raw('DATE(action_date) as date'),
                DB::raw('COUNT(*) as count')
            )
            ->groupBy('action_type_id', 'date')
            ->get();

        $datasets = [];
        $colors = [
            1 => '#36A2EB', 2 => '#FF6384', 3 => '#FFCE56',
            4 => '#4BC0C0', 5 => '#9966FF', 6 => '#FF9F40',
            7 => '#C9CBCF'
        ];

        foreach ($actionTypes as $type) {
            $typeData = $dates->mapWithKeys(function ($date) use ($actions, $type) {
                $count = $actions->where('action_type_id', $type->id)
                                ->where('date', $date)
                                ->sum('count');
                return [$date => $count];
            });

            if ($typeData->sum() > 0) {
                $datasets[] = [
                    'label' => $type->name,
                    'data' => $typeData->values()->all(),
                    'borderColor' => $colors[$type->id] ?? '#'.substr(md5($type->id), 0, 6),
                    'backgroundColor' => $colors[$type->id] ?? '#'.substr(md5($type->id), 0, 6),
                    'fill' => false,
                    'tension' => 0.1,
                    'borderWidth' => 2,
                ];
            }
        }

        return [
            'datasets' => $datasets,
            'labels' => $dates->map(fn($date) => Carbon::parse($date)->translatedFormat('d M'))->values(),
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
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'ticks' => [
                        'stepSize' => 1,
                    ],
                ],
            ],
            'plugins' => [
                'legend' => [
                    'position' => 'top',
                    'labels' => [
                        'boxWidth' => 15,
                        'usePointStyle' => true
                    ]
                ]
            ],
            'aspectRatio' => 0.8,
            'tension' => 0.5,
        ];
    }
}