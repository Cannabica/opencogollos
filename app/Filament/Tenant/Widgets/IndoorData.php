<?php

namespace App\Filament\Tenant\Widgets;

use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use App\Models\Indoor;
use Filament\Tables\Columns\TextColumn;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Illuminate\Database\Eloquent\Builder;

class IndoorData extends BaseWidget
{

    use InteractsWithPageFilters;

    protected function getTableHeading(): string
    {
        return __('Indoor Data'); // Título traducido
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Indoor::query()
                    ->when(
                        $this->filters['indoor'] ?? null, // Verifica si hay un filtro de indoor seleccionado.
                        fn (Builder $query, $indoorId) => $query->where('id', $indoorId) // Filtra por el ID del indoor.
                    )
            )
            ->columns([
                
                TextColumn::make('name')->label('Nombre'),
                TextColumn::make('large')->label('Largo'),
                TextColumn::make('width')->label('Ancho'),
                TextColumn::make('height')->label('Alto'),
                TextColumn::make('scheduled_days')->label('Días Programados'),
                TextColumn::make('data.fans')
                    ->label('Ventiladores')
                    ->getStateUsing(function ($record) {
                        // Verificamos si el campo 'data.fans' existe y si es un string de JSON válido.
                        $fans = $record->data['fans'] ?? null;
                        if ($fans) {
                            $fansArray = json_decode($fans, true);  // Decodificamos el JSON.
                            // Si la decodificación es válida y es un array, devolvemos el conteo.
                            return is_array($fansArray) ? count($fansArray) : 0;
                        }
                        return 0; // Si no hay 'fans' o no es válido, retornamos 0.
                    }),
                TextColumn::make('data.lamps')
                    ->label('Lámparas')
                    ->getStateUsing(function ($record) {
                        // Verificamos si el campo 'data.lamps' existe y si es un string de JSON válido.
                        $lamps = $record->data['lamps'] ?? null;
                        if ($lamps) {
                            $lampsArray = json_decode($lamps, true);  // Decodificamos el JSON.
                            // Si la decodificación es válida y es un array, devolvemos el conteo.
                            return is_array($lampsArray) ? count($lampsArray) : 0;
                        }
                        return 0; // Si no hay 'lamps' o no es válido, retornamos 0.
                    }),
                TextColumn::make('hygometer')->label('Higometro'),
                TextColumn::make('humidifier')->label('Humidificador'),
                TextColumn::make('peak_quantity')->label('Cantidad de Picos por planta'),
                TextColumn::make('scheduled_time')->label(''),
                TextColumn::make('times_a_day')->label(''),
                TextColumn::make('data.scheduled_days')->label(''),

            ]);
    }
}
