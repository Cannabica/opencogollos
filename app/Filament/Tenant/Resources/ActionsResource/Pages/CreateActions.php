<?php

namespace App\Filament\Tenant\Resources\ActionsResource\Pages;

use App\Filament\Tenant\Resources\ActionsResource;
use Filament\Actions;
use App\Models\Action;
use App\Models\Plant;
use Filament\Resources\Pages\CreateRecord;

class CreateActions extends CreateRecord
{
    protected static string $resource = ActionsResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function handleRecordCreation(array $data): Action
    {
        return Action::create($data);
    }

    protected function afterCreate()
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
        $actionClass = $action->action_type->action_class;

        if (class_exists($actionClass)) {
            foreach($plants as $plant) {
                // Obtener los argumentos de forma estática
                $constructorArgs = $actionClass::getConstructorArguments($action);

                // Crear la instancia real con los argumentos del constructor
                $actionInstance = new $actionClass();

                // Llamar al método trigger con la planta como argumento
                $actionInstance->trigger($plant, $constructorArgs);
            }
        }
    }

}
