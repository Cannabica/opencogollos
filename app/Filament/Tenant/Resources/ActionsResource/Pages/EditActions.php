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

    protected function afterSave()
    {
        // Obtén las plantas seleccionadas del formulario
        $selectedPlants = $this->record->data['plants'] ?? [];

        // Sincroniza las plantas seleccionadas con la tabla pivote
        $this->record->plants()->sync($selectedPlants);
        $this->executeActionTrigger($this->record); 
    }

    protected function executeActionTrigger($action)
    {
        $plants = $action->plants()->get();

        foreach ($plants as $plant) {
            $actionClass = $action->action_type->action_class;

            if (class_exists($actionClass)) {
                // Obtener los datos necesarios para la acción
                $data = $actionClass::getConstructorArguments($action);

                // Crear una instancia de la acción
                $actionInstance = new $actionClass();

                // Llamar al método trigger con la planta y los datos
                $actionInstance->trigger($plant, $data);
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
