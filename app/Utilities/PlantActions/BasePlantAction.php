<?php

namespace App\Utilities\PlantActions;

use App\Models\Plant;

class BasePlantAction
{

    public function trigger(Plant $plant, ?array $data = null)
    {}

    public function disclaimer()
    {}

    public static function getConstructorArguments($action)
    {
        // Por defecto, no requiere argumentos adicionales
        return [];
    }

}
