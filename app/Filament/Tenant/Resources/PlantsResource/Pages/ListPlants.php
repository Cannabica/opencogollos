<?php

namespace App\Filament\Tenant\Resources\PlantsResource\Pages;

use App\Filament\Tenant\Resources\PlantsResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\HtmlString;
use App\Models\Plant;
use Illuminate\Database\Eloquent\Builder;

class ListPlants extends ListRecords
{
    protected static string $resource = PlantsResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->icon('heroicon-o-plus'),
        ];
    }

    public function getSubheading(): string|Htmlable|null 
    {
        $tenantId = auth()->user()->tenant_id;
        \Log::info('ListPlants - Current tenant ID: ' . $tenantId);

        $baseQuery = Plant::query()
            ->whereHas('indoor', function ($query) use ($tenantId) {
                $query->where('tenant_id', $tenantId);
            });

        $germinationCount = (clone $baseQuery)->where('state', 'Etapa de Germinación')->count();
        $seedlingCount = (clone $baseQuery)->where('state', 'Etapa de Plantula')->count();
        $vegetativeCount = (clone $baseQuery)->where('state', 'Etapa Vegetativa')->count();
        $floweringCount = (clone $baseQuery)->where('state', 'Etapa Floracion')->count();
        $totalPlants = (clone $baseQuery)->where('state', '!=', 'muerta')->count();

        return new HtmlString("
            <div class='space-y-2'>
                <p class='fi-header-subheading'>
                    " . __('subheading_plant') . "
                </p>
                <div class='plant-stats-container'>
                    <div class='plant-stats-total'>
                        Total de plantas activas: {$totalPlants}
                    </div>
                    <div class='plant-stats-item'>
                        <span>Germinación:</span>
                        <strong>{$germinationCount}</strong>
                    </div>
                    <div class='plant-stats-item'>
                        <span>Plántula:</span>
                        <strong>{$seedlingCount}</strong>
                    </div>
                    <div class='plant-stats-item'>
                        <span>Vegetativo:</span>
                        <strong>{$vegetativeCount}</strong>
                    </div>
                    <div class='plant-stats-item'>
                        <span>Floración:</span>
                        <strong>{$floweringCount}</strong>
                    </div>
                </div>
            </div>
        ");
    }

    protected function getTableQuery(): Builder
    {
        $query = parent::getTableQuery();
        
        // Log the final table query
        \Log::info('ListPlants table query SQL: ' . $query->toSql());
        \Log::info('ListPlants table query bindings: ' . json_encode($query->getBindings()));
        \Log::info('ListPlants table query count: ' . $query->count());

        return $query;
    }
}