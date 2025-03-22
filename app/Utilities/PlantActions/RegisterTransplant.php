<?php

namespace App\Utilities\PlantActions;

use App\Models\Plant;
use InvalidArgumentException;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Log;

class RegisterTransplant extends BasePlantAction
{
    public function trigger(Plant $plant, ?array $data = null)
    {
        try {
            \Log::debug('Iniciando trigger de transplante para planta', [
                'plant_id' => $plant->id
            ]);

            // Obtener la acción directamente desde la relación actions
            $action = $plant->actions()->latest()->first();

            if (!$action) {
                throw new InvalidArgumentException("No se encontró la acción asociada a la planta {$plant->id}");
            }

            \Log::debug('Acción encontrada', [
                'action_id' => $action->id,
                'plant_id' => $plant->id
            ]);

            $transplantData = $this->getDataFromAction($action);

            \Log::debug('Actualizando planta con nuevos datos', [
                'plant_id' => $plant->id,
                'datos_nuevos' => $transplantData
            ]);

            // Actualizar la planta
            $plant->update([
                'flowerpot' => $transplantData['flowerpot'],
                'capacity' => $transplantData['capacity']
            ]);

            \Log::info('Transplante completado exitosamente', [
                'plant_id' => $plant->id,
                'action_id' => $action->id
            ]);

        } catch (\Exception $e) {
            \Log::error('Error en trigger de transplante', [
                'plant_id' => $plant->id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    protected function getDataFromAction($action)
    {
        if (!$action->data || 
            !isset($action->data['transplant']) || 
            !isset($action->data['transplant']['new_flowerpot']) || 
            !isset($action->data['transplant']['new_capacity'])) {
            
            \Log::error('Datos de transplante inválidos', [
                'action_id' => $action->id,
                'data' => $action->data ?? null
            ]);
            
            throw new InvalidArgumentException(
                "Datos de transplante incompletos para la acción {$action->id}"
            );
        }

        return [
            'flowerpot' => $action->data['transplant']['new_flowerpot'],
            'capacity' => $action->data['transplant']['new_capacity']
        ];
    }

    public static function getConstructorArguments($action)
    {
        return [];
    }
}
