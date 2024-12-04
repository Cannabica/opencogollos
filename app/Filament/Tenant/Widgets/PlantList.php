<?php

namespace App\Filament\Tenant\Widgets;

use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use App\Models\Plant;
use Filament\Tables\Columns\TextColumn;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Illuminate\Database\Eloquent\Builder;

class PlantList extends BaseWidget
{

    use InteractsWithPageFilters;

    protected function getTableHeading(): string
    {
        return __('Plant List'); // Título traducido
    }
    
    public function table(Table $table): Table
    {
        return $table
            ->query(
                Plant::query()
                    ->when(
                        $this->filters['indoor'] ?? null, // Verifica si hay un filtro de indoor seleccionado.
                        fn (Builder $query, $indoorId) => $query->where('indoor_id', $indoorId) // Filtra por el ID del indoor.
                    )
            )
            ->columns([
                
                TextColumn::make('name')->label('Nombre'),
                TextColumn::make('seedType.name')->label('Tipo de Semilla'),
                TextColumn::make('germination_date')->label('Fecha de Germinación'),

            ]);
    }
}
