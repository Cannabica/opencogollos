<?php

namespace App\Utilities\PlantActions;

use App\Models\Plant;

class RegisterDeath extends BasePlantAction
{
    public function trigger(Plant $plant, ?array $data = null)
    {

        $plant->update(['state' => 'muerta']);

    }
}
