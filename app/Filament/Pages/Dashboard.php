<?php
 
namespace App\Filament\Pages;
 
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Section;
use Filament\Forms\Form;
use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Pages\Dashboard\Concerns\HasFiltersForm;
use App\Models\Indoor;
use App\Models\Plant;
use App\Filament\Tenant\Widgets\PlantList;
use App\Filament\Tenant\Widgets\IndoorWidget;
use Illuminate\Database\Eloquent\Builder;
 
class Dashboard extends BaseDashboard
{
    use HasFiltersForm;
 
    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static string $view = 'filament.tenant.pages.custom-dashboard';

    public function filtersForm(Form $form): Form
    {
        return $form
            ->schema([
                Select::make('indoor')
                ->label('Seleccionar Indoor')
                ->options(
                    Indoor::where('tenant_id', auth()->user()->tenant_id) 
                    ->pluck('name', 'id') 
                    ->toArray()                 
                )
                ->searchable()
                ->placeholder('Selecciona un Indoor')
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
                fn (Builder $query) => $query->where('tenant_id', auth()->user()->tenant_id) // Filtra por tenant_id
            )
            ->when(
                $this->filters['indoor'] ?? null, // Verifica si hay un filtro de indoor seleccionado
                fn (Builder $query, $indoorId) => $query->where('id', $indoorId) // Filtra por el ID del indoor
            )
            ->get();
    }

    public function getPlants(): \Illuminate\Database\Eloquent\Collection
    {
        return Plant::query()
            ->when(
                $this->filters['indoor'] ?? null, // Verifica si hay un filtro de indoor seleccionado.
                fn (Builder $query, $indoorId) => $query->where('indoor_id', $indoorId) // Filtra por el ID del indoor.
            )
            ->get();
    }
}