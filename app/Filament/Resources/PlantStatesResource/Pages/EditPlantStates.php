<?php

namespace App\Filament\Resources\PlantStatesResource\Pages;

use App\Filament\Resources\PlantStatesResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPlantStates extends EditRecord
{
    protected static string $resource = PlantStatesResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
