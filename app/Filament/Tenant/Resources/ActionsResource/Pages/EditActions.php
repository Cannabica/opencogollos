<?php

namespace App\Filament\Tenant\Resources\ActionsResource\Pages;

use App\Filament\Tenant\Resources\ActionsResource;
use Filament\Actions;
use Illuminate\Database\Eloquent\Model;
use App\Models\Action;
use App\Models\Plant;
use Filament\Resources\Pages\EditRecord;
use Filament\Actions\Action as FilamentAction;
use Filament\Actions\DeleteAction;

class EditActions extends EditRecord
{
    protected static string $resource = ActionsResource::class;

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        //dd($data);
        $record->update($data);

        return $record;
    }

    protected function afterSave(): void
    {
        // Execute the action trigger
        $this->executeActionTrigger($this->record);
    }

    protected function executeActionTrigger($action)
    {
        $actionClass = $action->action_type->action_class;

        if (class_exists($actionClass)) {
            $actionInstance = new $actionClass();
            
            // Execute the trigger for each related plant
            foreach ($action->plants as $plant) {
                $actionInstance->trigger($plant);
            }
        }
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->icon('heroicon-o-trash'),
        ];
    }

    protected function getFormActions(): array
    {
        return [
            FilamentAction::make('save')
                ->label(__('Guardar'))
                ->icon('heroicon-o-check')
                ->submit('save'),
            
            FilamentAction::make('cancel')
                ->label(__('Cancelar'))
                ->icon('heroicon-o-x-mark')
                ->color('gray')
                ->url($this->getResource()::getUrl('index')),
        ];
    }
}
