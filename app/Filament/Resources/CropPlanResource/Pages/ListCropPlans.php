<?php

namespace App\Filament\Resources\CropPlanResource\Pages;

use App\Filament\Resources\CropPlanResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListCropPlans extends ListRecords
{
    protected static string $resource = CropPlanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
