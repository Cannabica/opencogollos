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
        // (el trait InteractsWithPageFilters SOLO expone `public ?array $filters`; no existe getFilter() —
        //  este widget llamaba a ese metodo inexistente y moria con BadMethodCallException. Corregido 2026-09-18.)
        $indoorId = $this->filters['indoor'] ?? null;

        // Obtener el indoor seleccionado o el último disponible
        // (Indoor tiene TenantScope: es SIEMPRE el indoor del tenant del usuario)
        $indoor = $indoorId 
            ? Indoor::find($indoorId) 
            : Indoor::latest()->first();

        return [
            'indoor' => $indoor,
        ];
    }

    /**
     * La vista recibe UNICAMENTE lo que devuelve este metodo (Filament v3: Widget::render() hace
     * `view(static::$view, $this->getViewData())`). Antes este widget solo tenia `getData()` —un
     * metodo que en un Widget plano NO alimenta la vista— asi que el blade moria con
     * "Undefined variable $indoor" y el widget no renderizaba para nadie (corregido 2026-09-18).
     */
    protected function getViewData(): array
    {
        return $this->getData();
    }
}
