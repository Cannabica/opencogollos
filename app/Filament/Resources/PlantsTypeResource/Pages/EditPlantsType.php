<?php

namespace App\Filament\Resources\PlantsTypeResource\Pages;

use App\Filament\Resources\PlantsTypeResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPlantsType extends EditRecord
{
    protected static string $resource = PlantsTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
