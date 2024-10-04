<?php

namespace App\Filament\Resources\PlantStatesResource\Pages;

use App\Filament\Resources\PlantStatesResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPlantStates extends ListRecords
{
    protected static string $resource = PlantStatesResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
