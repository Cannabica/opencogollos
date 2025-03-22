<?php

namespace App\Filament\Tenant\Resources\PlantsResource\Pages;

use App\Filament\Tenant\Resources\PlantsResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Filament\Actions\Action;

class EditPlants extends EditRecord
{
    protected static string $resource = PlantsResource::class;

    protected function getActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->icon('heroicon-o-trash'),
        ];
    }

    protected function getSaveFormAction(): Action
    {
        return parent::getSaveFormAction()
            ->icon('heroicon-o-check');
    }

    protected function getCancelFormAction(): Action
    {
        return parent::getCancelFormAction()
            ->icon('heroicon-o-x-mark');
    }
}
