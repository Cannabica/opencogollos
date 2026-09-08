<?php

namespace App\Filament\Tenant\Widgets;

use App\Models\Indoor;
use App\Models\Plant;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class PlantStatesByIndoorWidget extends ChartWidget
{
    protected static ?string $heading = 'Estados de Plantas por Indoor';

    protected static array $colors = [
        'rgb(111, 211, 44)',
        'rgb(55, 152, 216)',
        'rgb(235, 123, 18)',
        'rgb(110, 70, 255)',
        'rgba(111, 67, 199, 0.8)',
    ];

    protected function getData(): array
    {
        $indoors = Indoor::where('tenant_id', auth()->user()->tenant_id)
            ->with('plants')
            ->get();
        $states = Plant::whereHas('indoor', function($query) {
                $query->where('tenant_id', auth()->user()->tenant_id);
            })
            ->where('state', '!=', 'muerta')
            ->distinct('state')
            ->pluck('state')
            ->filter();
        
        $datasets = $states->map(function ($state, $index) use ($indoors) {
            return [
                'label' => ucfirst($state),
                'data' => $indoors->map(function ($indoor) use ($state) {
                    return $indoor->plants->where('state', $state)->count();
                })->toArray(),
                'backgroundColor' => self::$colors[$index % count(self::$colors)],
            ];
        });

        return [
            'datasets' => $datasets->toArray(),
            'labels' => $indoors->pluck('name')->toArray(),
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getOptions(): array
    {
        return [
            'scales' => [
                'x' => [
                    'stacked' => true,
                ],
                'y' => [
                    'stacked' => true,
                ],
            ],
            'aspectRatio' => 1,
        ];
    }
} 