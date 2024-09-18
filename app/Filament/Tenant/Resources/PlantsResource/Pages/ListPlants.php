<?php

namespace App\Filament\Tenant\Resources\PlantsResource\Pages;

use App\Filament\Tenant\Resources\PlantsResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPlants extends ListRecords
{
    protected static string $resource = PlantsResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
