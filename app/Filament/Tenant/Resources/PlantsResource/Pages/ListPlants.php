<?php

namespace App\Filament\Tenant\Resources\PlantsResource\Pages;

use App\Filament\Tenant\Resources\PlantsResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\HtmlString;

class ListPlants extends ListRecords
{
    protected static string $resource = PlantsResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    public function getSubheading(): string|Htmlable|null 
    {
        // Obtener el conteo de plantas por estado
        $germinationCount = $this->getModel()::where('state', 'Etapa de Germinación')->count();
        $seedlingCount = $this->getModel()::where('state', 'Etapa de Plantula')->count();
        $vegetativeCount = $this->getModel()::where('state', 'Etapa Vegetativa')->count();
        $floweringCount = $this->getModel()::where('state', 'Etapa Floracion')->count();
        $totalPlants = $this->getModel()::count();

        return new HtmlString("
            <div class='space-y-2'>
                <p class='fi-header-subheading mt-2 text-lg text-gray-600 dark:text-gray-400'>
                    " .__('subheading_plant') ."
                </p>
                <div class='fi-header-subheading mt-2 text-lg text-gray-600 dark:text-gray-400'>
                    <span class='font-medium'><strong>Total de plantas: {$totalPlants}</strong></span>
                    <span class='text-gray-300'>|</span>
                    <span>En germinación: {$germinationCount}</span>
                    <span class='text-gray-300'>|</span>
                    <span>En plantula: {$seedlingCount}</span>
                    <span class='text-gray-300'>|</span>
                    <span>En vegetativo: {$vegetativeCount}</span>
                    <span class='text-gray-300'>|</span>
                    <span>En floración: {$floweringCount}</span>
                </div>
            </div>
        ");
    }
}