<?php

namespace App\Filament\Pages;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Section;
use Filament\Forms\Form;
use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Pages\Dashboard\Concerns\HasFiltersForm;
use Filament\Support\Enums\MaxWidth;
use App\Models\Indoor;
use App\Models\Plant;
use App\Filament\Tenant\Widgets\PlantList;
use App\Filament\Tenant\Widgets\IndoorWidget;
use Illuminate\Database\Eloquent\Builder;

class Dashboard extends BaseDashboard
{
    use HasFiltersForm;

    protected static ?string $navigationIcon = 'heroicon-o-chart-bar-square';

    protected static string $view = 'filament.tenant.pages.custom-dashboard';
    protected static ?string $title = 'Dashboard';

    /**
     * El dashboard trabaja con CARRILES horizontales: cuantos más px de ancho,
     * más espacios visibles a la vez. El default de Filament (max-w-7xl ≈ 1280px)
     * dejaba ~700px sin usar en un monitor 16:9. Se amplía solo esta página.
     */
    public function getMaxContentWidth(): MaxWidth | string | null
    {
        return MaxWidth::Full;
    }


    public function filtersForm(Form $form): Form
    {
        return $form
            ->schema([
                Select::make('indoor')
                    ->label('Ver un espacio')
                    ->options(
                        Indoor::where('tenant_id', auth()->user()->tenant_id)
                            ->pluck('name', 'id')
                            ->toArray()
                    )
                    ->searchable()
                    ->placeholder('Todos los espacios')
                    ->reactive(),
            ]);
    }

    public function getWidgets(): array
    {
        // Asegúrate de agregar widgets correctamente
        return [
            PlantList::class,
            // IndoorWidget::class, // O cualquier widget personalizado
        ];
    }

    public function getIndoors(): \Illuminate\Database\Eloquent\Collection
    {
        return Indoor::query()
            ->when(
                auth()->check() && auth()->user()->tenant_id, // Verifica que el usuario esté autenticado y tenga un tenant_id
                fn(Builder $query) => $query->where('tenant_id', auth()->user()->tenant_id) // Filtra por tenant_id
            )
            ->when(
                $this->filters['indoor'] ?? null, // Verifica si hay un filtro de indoor seleccionado
                fn(Builder $query, $indoorId) => $query->where('id', $indoorId) // Filtra por el ID del indoor
            )
            ->get();
    }

    public function getPlants(int $indoorId): \Illuminate\Database\Eloquent\Collection
    {
        return Plant::query()
            ->whereHas('indoor', function ($query) {
                $query->where('tenant_id', auth()->user()->tenant_id);
            })
            ->where('indoor_id', $indoorId)
            ->get();
    }

    public function getLastActionsForIndoor(int $indoorId, int $limit = 4): \Illuminate\Database\Eloquent\Collection
    {
        return \App\Models\Action::query()
            ->where('indoor_id', $indoorId) // Filtra por el ID del indoor
            ->orderBy('action_date', 'desc') // Ordena por la fecha de la acción, descendente
            ->limit($limit) // Limita el número de resultados
            ->get();
    }

}
