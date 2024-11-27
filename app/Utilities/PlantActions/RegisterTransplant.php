<?php

namespace App\Utilities\PlantActions;

use App\Models\Plant;

class RegisterTransplant extends BasePlantAction
{
    protected $newPot;

    /*public function __construct($newPot){
        $this->newPot = $newPot;
    }*/

    public function trigger(Plant $plant, ?array $data = null){
        // Cambiar el dato de la maceta de la planta
        $plant->update(['flowerpot' => $data['pot']]);
    }

    public static function getConstructorArguments($action)
    {
        // Retorna el argumento específico requerido para esta acción
        return ['pot' => $action->data['transplant']['new_pot_size']];
    }
}
