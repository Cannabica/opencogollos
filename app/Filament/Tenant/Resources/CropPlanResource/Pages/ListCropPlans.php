<?php

namespace App\Filament\Tenant\Resources\CropPlanResource\Pages;

use App\Filament\Tenant\Resources\CropPlanResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListCropPlans extends ListRecords
{
    protected static string $resource = CropPlanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
            ->icon('heroicon-o-plus'),
        ];
    }
}
