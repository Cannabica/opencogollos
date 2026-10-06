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
use App\Support\IrrigationSignals;
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
     * El dashboard es un muro a pantalla completa: no lleva el encabezado
     * "Dashboard" del layout (el resumen va dentro de la primera tarjeta).
     */
    public function getHeading(): string | \Illuminate\Contracts\Support\Htmlable
    {
        return '';
    }

    /**
     * El dashboard trabaja con un muro de tarjetas: cuantos más px de ancho,
     * más tarjetas visibles a la vez. El default de Filament (max-w-7xl ≈ 1280px)
     * dejaba ~700px sin usar en un monitor 16:9. Se amplía solo esta página.
     */
    public function getMaxContentWidth(): MaxWidth | string | null
    {
        return MaxWidth::Full;
    }

    /**
     * Los espacios del panel, con sus plantas y las SEÑALES DE TRABAJO de riego.
     *
     * El último riego sale de datos que ya existen (acciones de tipo 1 + pivote
     * action_plant) y la frecuencia esperada, de la programación que el espacio
     * ya guarda. Dos consultas para todo el panel, sin N+1.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getSpaces(): array
    {
        $tenantId = auth()->user()->tenant_id;
        $ultimoPorPlanta = IrrigationSignals::lastIrrigationByPlant($tenantId);
        $ultimoPorEspacio = IrrigationSignals::lastIrrigationBySpace($tenantId);

        return $this->getIndoors()->map(function (Indoor $indoor) use ($ultimoPorPlanta, $ultimoPorEspacio) {
            $plantas = $this->getPlants($indoor->id);
            $vecesPorDia = $indoor->times_a_day;

            $senalesPlantas = $plantas->mapWithKeys(fn (Plant $planta) => [
                $planta->id => IrrigationSignals::state($ultimoPorPlanta[$planta->id] ?? null, $vecesPorDia),
            ]);

            return [
                'indoor' => $indoor,
                'plantas' => $plantas,
                'estados' => $plantas->groupBy('state'),
                'riego' => IrrigationSignals::state($ultimoPorEspacio[$indoor->id] ?? null, $vecesPorDia),
                'riego_plantas' => $senalesPlantas,
                'plantas_sin_riego' => $senalesPlantas->filter(
                    fn ($senal) => $senal['estado'] === IrrigationSignals::SIN_RIEGO
                )->count(),
                'plantas_atrasadas' => $senalesPlantas->filter(
                    fn ($senal) => $senal['estado'] === IrrigationSignals::ATRASADO
                )->count(),
                'acciones' => $this->getLastActionsForIndoor($indoor->id),
            ];
        })->values()->all();
    }

    /**
     * Totales de trabajo del panel: lo que pide atención hoy.
     *
     * @param  array<int, array<string, mixed>>  $spaces
     * @return array{espacios: int, plantas: int, espacios_sin_riego: int, espacios_atrasados: int, plantas_sin_riego: int}
     */
    public function getWorkSummary(array $spaces): array
    {
        $espaciosSinRiego = 0;
        $espaciosAtrasados = 0;
        $plantasSinRiego = 0;
        $totalPlantas = 0;

        foreach ($spaces as $space) {
            if ($space['riego']['estado'] === IrrigationSignals::SIN_RIEGO) {
                $espaciosSinRiego++;
            }
            if ($space['riego']['estado'] === IrrigationSignals::ATRASADO) {
                $espaciosAtrasados++;
            }
            $plantasSinRiego += $space['plantas_sin_riego'];
            $totalPlantas += $space['plantas']->count();
        }

        return [
            'espacios' => count($spaces),
            'plantas' => $totalPlantas,
            'espacios_sin_riego' => $espaciosSinRiego,
            'espacios_atrasados' => $espaciosAtrasados,
            'plantas_sin_riego' => $plantasSinRiego,
        ];
    }


    public function filtersForm(Form $form): Form
    {
        return $form
            ->schema([
                Select::make('indoor')
                    ->label('Filtrar por lugar')
                    ->options(
                        Indoor::where('tenant_id', auth()->user()->tenant_id)
                            ->withCount('plants')
                            ->get()
                            ->mapWithKeys(function (Indoor $indoor) {
                                $cuantas = $indoor->plants_count;

                                return [$indoor->id => $cuantas
                                    ? $indoor->name . ' · ' . $cuantas . ($cuantas === 1 ? ' planta' : ' plantas')
                                    : $indoor->name . ' · sin plantas'];
                            })
                            ->toArray()
                    )
                    ->searchable()
                    ->placeholder('Todos los lugares')
                    ->live(),
            ]);
    }

    /**
     * Cuántos lugares tiene el grupo (sin contar el filtro): decide si el selector
     * se muestra o si alcanza con el nombre del único lugar.
     */
    public function getTotalIndoors(): int
    {
        return Indoor::query()
            ->when(
                auth()->check() && auth()->user()->tenant_id,
                fn (Builder $query) => $query->where('tenant_id', auth()->user()->tenant_id)
            )
            ->count();
    }

    public function getWidgets(): array
    {
        // Asegúrate de agregar widgets correctamente
        return [
            PlantList::class,
            // IndoorWidget::class, // O cualquier widget personalizado
        ];
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection<int, Indoor>
     */
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

    /**
     * @return \Illuminate\Database\Eloquent\Collection<int, Plant>
     */
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
