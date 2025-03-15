<?php

namespace App\Filament\Tenant\Resources\ActionsResource\Pages;

use App\Filament\Tenant\Resources\ActionsResource;
use Filament\Actions;
use Illuminate\Database\Eloquent\Model;
use App\Models\Action;
use App\Models\Plant;
use Filament\Resources\Pages\EditRecord;

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
            Actions\DeleteAction::make(),
        ];
    }
}
