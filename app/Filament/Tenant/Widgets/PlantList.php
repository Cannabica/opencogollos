<?php

namespace App\Filament\Tenant\Widgets;

use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use App\Models\Plant;

class PlantList extends BaseWidget
{
    public function table(Table $table): Table
    {
        return $table
            ->query(
                Plant::query()
            )
            ->columns([
                
                Tables\Columns\TextColumn::make('name')->label('Nombre'),
                Tables\Columns\TextColumn::make('seedType.name')->label('Tipo de Semilla'),
                Tables\Columns\TextColumn::make('germination_date')->label('Fecha de Germinación'),

            ]);
    }
}
