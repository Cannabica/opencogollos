<?php

namespace App\Filament\Tenant\Widgets;

use App\Models\Indoor;
use App\Models\Plant;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class PlantStatesByIndoorWidget extends ChartWidget
{
    protected static ?string $heading = 'Estados de Plantas por Indoor';

    protected function getData(): array
    {
        $indoors = Indoor::with('plants')->get();
        $states = Plant::distinct('state')->pluck('state')->filter();
        
        $datasets = $states->map(function ($state) use ($indoors) {
            return [
                'label' => ucfirst($state),
                'data' => $indoors->map(function ($indoor) use ($state) {
                    return $indoor->plants->where('state', $state)->count();
                })->toArray(),
                'backgroundColor' => 'rgba(' . rand(0, 255) . ',' . rand(0, 255) . ',' . rand(0, 255) . ',0.8)',
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
        ];
    }
} 