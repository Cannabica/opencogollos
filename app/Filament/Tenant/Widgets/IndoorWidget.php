<?php

namespace App\Filament\Tenant\Widgets;

use Filament\Widgets\Widget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use App\Models\Indoor;

class IndoorWidget extends Widget
{
    use InteractsWithPageFilters;

    protected static string $view = 'filament.tenant.widgets.indoor-widget';

    public function getData(): array
    {
        // Obtener el ID del filtro de indoor
        $indoorId = $this->getFilter('indoor');

        // Obtener el indoor seleccionado o el último disponible
        $indoor = $indoorId 
            ? Indoor::find($indoorId) 
            : Indoor::latest()->first();

        return [
            'indoor' => $indoor,
        ];
    }
}
