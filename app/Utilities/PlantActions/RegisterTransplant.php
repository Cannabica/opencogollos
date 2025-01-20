<?php

namespace App\Utilities\PlantActions;

use App\Models\Plant;

class RegisterTransplant extends BasePlantAction
{
    public function trigger(Plant $plant, ?array $data = null)
    {
        // Cambiar el dato de la maceta de la planta
        $plant->update(['flowerpot' => $data['pot']]);
    }

    public static function getConstructorArguments($action)
    {
        // Retorna los datos necesarios para esta acción
        return ['pot' => $action->data['transplant']['new_pot_size'] ?? null];
    }
}
