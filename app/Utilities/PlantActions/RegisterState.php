<?php

namespace App\Utilities\PlantActions;

use App\Models\Plant;

class RegisterState extends BasePlantAction
{
    protected $newState;

    /*public function __construct($newState)
    {
        $this->newState = $newState;
    }*/

    public function trigger(Plant $plant, ?array $data = null)
    {
        // El data que persiste el panel viene anidado (data.change_state.state);
        // se acepta también la forma plana ['state' => ...] de getConstructorArguments.
        $state = $data['change_state']['state'] ?? $data['state'] ?? null;

        if (blank($state)) {
            return;
        }

        // Cambiar el estado de la planta al especificado en $newState
        $plant->update(['state' => $state]);
    }

    public static function getConstructorArguments($action)
    {
        // Retorna el argumento específico requerido para esta acción
        return ['state' => $action->data['change_state']['state']];
    }
}
