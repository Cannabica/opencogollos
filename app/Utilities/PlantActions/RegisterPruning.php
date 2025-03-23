<?php

namespace App\Utilities\PlantActions;

use App\Models\Action;
use Illuminate\Support\Carbon;

class RegisterPruning extends BasePlantAction
{

    public function disclaimer()
    {
        $className = $this::class;
        $actions = Action::whereHas('action_type', function ($query) use ($className) {
            $query->where('action_class', $className);
        })
            //TODO sumar estado no muerta y del tenant
            ->whereDate('action_date', '>', Carbon::today()->subDays(15))->get();

        if($actions->isEmpty()) return false;

        foreach ($actions as $action) {
            $return = '<p>Las siguientes plantas fueron podadas hace menos de 15 días:</p>';
            $return .= '<ul>';
            foreach ($action->plants as $plant) {
                $indoorName = $plant->indoor ? $plant->indoor->name : 'Sin ubicación';
                $return .= '<li> - '.$plant->name.' ('.$indoorName.')'.'</li>';
            }
            $return .= '</ul>';
            return $return;
        }
    }

}
