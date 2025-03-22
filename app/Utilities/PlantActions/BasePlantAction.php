<?php

namespace App\Utilities\PlantActions;

use App\Models\Plant;
use Illuminate\Support\Facades\DB;

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

    public function createActionWithPlants($data, $plants)
    {
        return DB::transaction(function() use ($data, $plants) {
            $action = Action::create($data);
            $action->plants()->sync($plants);
            // Ejecutar trigger solo después de confirmar la sincronización
            return $action;
        });
    }

}
