<?php

namespace App\Utilities\PlantActions;

use App\Models\Plant;

class RegisterState extends BasePlantAction
{
    protected $newState;

    public function __construct($newState){
        $this->newState = $newState;
    }

    public function trigger(Plant $plant){
        // Cambiar el estado de la planta al especificado en $newState
        $plant->update(['state' => $this->newState]);
    }

    public static function getConstructorArguments($action)
    {
        // Retorna el argumento específico requerido para esta acción
        return [$action->data['change_state']['state']];
    }
}
