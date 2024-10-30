<?php

namespace App\Filament\Tenant\Widgets;

use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use App\Models\Indoor;

class IndoorData extends BaseWidget
{
    public function table(Table $table): Table
    {
        return $table
            ->query(
                Indoor::query()
            )
            ->columns([
                
                Tables\Columns\TextColumn::make('name')->label('Nombre'),
                Tables\Columns\TextColumn::make('large')->label('Largo'),
                Tables\Columns\TextColumn::make('width')->label('Ancho'),
                Tables\Columns\TextColumn::make('height')->label('Alto'),
                Tables\Columns\TextColumn::make('scheduled_days')->label('Días Programados'),
                

            ]);
    }
}
