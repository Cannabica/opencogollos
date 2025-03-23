<?php

namespace App\Filament\Tenant\Resources\PlantsResource\Pages;

use App\Filament\Tenant\Resources\PlantsResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Filament\Actions\Action;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

class EditPlants extends EditRecord
{
    protected static string $resource = PlantsResource::class;

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        Log::info('Datos recibidos en EditPlants:', [
            'id' => $record->id,
            'datos_formulario' => $data,
            'tenant_id' => auth()->user()->tenant_id
        ]);

        $record->update($data);

        return $record;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

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
