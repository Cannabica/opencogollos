<?php

namespace App\Filament\Tenant\Resources\Actions\Components;

use Filament\Forms\Components\CheckboxList;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use App\Models\Plant;

// Componente para seleccionar plantas para una acción
// Después de crear la acción, no se puede editar la lista de plantas asociadas

class PlantSelector
{
    public static function make(): CheckboxList
    {
        return CheckboxList::make('plants')
            ->relationship(
                'plants',
                'name',
                modifyQueryUsing: fn ($query) => $query
                    ->select(['plants.id', 'plants.name'])
                    ->where('state', '!=', 'muerta')
            )
            ->options(function (callable $get, ?Model $record = null) {
                $indoor_id = $get('indoor_id');
                
                Log::debug('🔍 Obteniendo plantas vivas para selector', [
                    'indoor_id' => $indoor_id,
                    'state' => $get('plants'),
                ]);

                if (!$indoor_id) return [];

                $plants = Plant::query()
                    ->select(['id', 'name'])
                    ->where('indoor_id', $indoor_id)
                    ->where('state', '!=', 'muerta')
                    ->orderBy('name')
                    ->get()
                    ->mapWithKeys(function ($plant) {
                        return [$plant->id => $plant->name];
                    })
                    ->toArray();

                Log::debug('🌱 Plantas disponibles (no muertas)', [
                    'count' => count($plants),
                    'indoor_id' => $indoor_id
                ]);

                return $plants;
            })
            ->afterStateUpdated(function ($state, callable $get, callable $set) {
                Log::debug('📝 Plantas seleccionadas actualizadas', [
                    'selected' => $state,
                    'indoor_id' => $get('indoor_id')
                ]);
            })
            ->required()
            ->dehydrated(true)
            ->live()
            ->columns(2)
            ->bulkToggleable();
    }
}