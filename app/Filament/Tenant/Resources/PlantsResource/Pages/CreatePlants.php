<?php

namespace App\Filament\Tenant\Resources\PlantsResource\Pages;

use App\Filament\Tenant\Resources\PlantsResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Filament\Actions\Action;

class CreatePlants extends CreateRecord
{
    protected static string $resource = PlantsResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getFormActions(): array
    {
        return [
            $this->getCreateFormAction()
                ->icon('heroicon-o-check'),
            $this->getCreateAnotherFormAction()
                ->icon('heroicon-o-plus-circle'),
            $this->getCancelFormAction()
                ->icon('heroicon-o-x-mark'),
        ];
    }

    protected function getCreateFormAction(): Action
    {
        return parent::getCreateFormAction()
            ->icon('heroicon-o-plus')
            ->label(__('Create Plant'));
    }

    protected function getCancelFormAction(): Action
    {
        return parent::getCancelFormAction()
            ->icon('heroicon-o-x-mark');
    }
}
