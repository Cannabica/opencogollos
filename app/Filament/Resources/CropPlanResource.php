<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CropPlanResource\Pages;
use App\Filament\Resources\CropPlanResource\RelationManagers;
use App\Models\CropPlan;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Fieldset;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Checkbox;

class CropPlanResource extends Resource
{
    protected static ?string $model = CropPlan::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function getPluralLabel(): string
    {
        return __('Crop Plans');
    }
    public static function getLabel(): string
    {
        return __('Crop Plan');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                
                Section::make('Datos Generales')
                    ->schema([
                        
                        TextInput::make('rest_pruning')
                            ->label('Descanso sugerido entre podas (en días)')
                            ->numeric()
                            ->required(),

                        TextInput::make('rest_fert')
                            ->label('Descanso entre fertilizaciones (en días)')
                            ->numeric()
                            ->required(),

                        TextInput::make('stop_fert')
                            ->label('Dejar de fertilizar antes de fecha de corte (en días)')
                            ->numeric()
                            ->required(),

                        TextInput::make('irrigation')
                            ->label('Irrigación de maceta sugerida (en %)')
                            ->numeric()
                            ->required(),
                           
                    ]),
                
                Section::make('Etapa de germinación')
                    ->schema([
                        
                        TextInput::make('germination_since')
                            ->label('Período comprendido desde')
                            ->numeric()
                            ->required(),

                        TextInput::make('germination_until')
                            ->label('Período comprendido hasta')
                            ->numeric()
                            ->required(),

                        TextInput::make('germination_light')
                            ->label('Horas de luz')
                            ->numeric()
                            ->required(),

                        TextInput::make('germination_darkness')
                            ->label('Horas de oscuridad')
                            ->numeric()
                            ->required(),

                        TextInput::make('germination_humidity_since')
                            ->label('Humedad recomendada desde')
                            ->numeric()
                            ->required(),

                        TextInput::make('germination_humidity_until')
                            ->label('Humedad recomendada hasta')
                            ->numeric()
                            ->required(),

                        TextInput::make('germination_temp_since')
                            ->label('Temperatura recomendada desde')
                            ->numeric()
                            ->required(),

                        TextInput::make('germination_temp_until')
                            ->label('Temperatura recomendada hasta')
                            ->numeric()
                            ->required(),

                    ]),

                Section::make('Etapa Plantula')
                    ->schema([
                        
                        TextInput::make('plantula_since')
                            ->label('Período comprendido desde')
                            ->numeric()
                            ->required(),

                        TextInput::make('plantula_until')
                            ->label('Período comprendido hasta')
                            ->numeric()
                            ->required(),

                        TextInput::make('plantula_light')
                            ->label('Horas de luz')
                            ->numeric()
                            ->required(),

                        TextInput::make('plantula_darkness')
                            ->label('Horas de oscuridad')
                            ->numeric()
                            ->required(),

                        TextInput::make('plantula_humidity_since')
                            ->label('Humedad recomendada desde')
                            ->numeric()
                            ->required(),

                        TextInput::make('plantula_humidity_until')
                            ->label('Humedad recomendada hasta')
                            ->numeric()
                            ->required(),

                        TextInput::make('plantula_temp_since')
                            ->label('Temperatura recomendada desde')
                            ->numeric()
                            ->required(),

                        TextInput::make('plantula_temp_until')
                            ->label('Temperatura recomendada hasta')
                            ->numeric()
                            ->required(),                 

                    ]),

                Section::make('Etapa Vegetativa')
                    ->schema([
                        
                        TextInput::make('vegetative_since')
                            ->label('Período comprendido desde')
                            ->numeric()
                            ->required(),

                        TextInput::make('vegetative_until')
                            ->label('Período comprendido hasta')
                            ->numeric()
                            ->required(),

                        TextInput::make('vegetative_light')
                            ->label('Horas de luz')
                            ->numeric()
                            ->required(),

                        TextInput::make('vegetative_darkness')
                            ->label('Horas de oscuridad')
                            ->numeric()
                            ->required(),

                        TextInput::make('vegetative_humidity_since')
                            ->label('Humedad recomendada desde')
                            ->numeric()
                            ->required(),

                        TextInput::make('vegetative_humidity_until')
                            ->label('Humedad recomendada hasta')
                            ->numeric()
                            ->required(),

                        TextInput::make('vegetative_temp_since')
                            ->label('Temperatura recomendada desde')
                            ->numeric()
                            ->required(),

                        TextInput::make('vegetative_temp_until')
                            ->label('Temperatura recomendada hasta')
                            ->numeric()
                            ->required(),

                    ]),

                Section::make('Etapa Floracion')
                    ->schema([
                        
                        TextInput::make('flowering_since')
                            ->label('Período comprendido desde')
                            ->numeric()
                            ->required(),

                        TextInput::make('flowering_until')
                            ->label('Período comprendido hasta')
                            ->numeric()
                            ->required(),

                        TextInput::make('flowering_light')
                            ->label('Horas de luz')
                            ->numeric()
                            ->required(),

                        TextInput::make('flowering_darkness')
                            ->label('Horas de oscuridad')
                            ->numeric()
                            ->required(),

                        TextInput::make('flowering_humidity_since')
                            ->label('Humedad recomendada desde')
                            ->numeric()
                            ->required(),

                        TextInput::make('flowering_humidity_until')
                            ->label('Humedad recomendada hasta')
                            ->numeric()
                            ->required(),

                        TextInput::make('flowering_temp_since')
                            ->label('Temperatura recomendada desde')
                            ->numeric()
                            ->required(),

                        TextInput::make('flowering_temp_until')
                            ->label('Temperatura recomendada hasta')
                            ->numeric()
                            ->required(),

                    ]),

            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                // Resumen del ciclo de cultivo
                TextColumn::make('germination_since')
                    ->label('Germinación Desde')
                    ->sortable()
                    ->searchable(),
                
                TextColumn::make('germination_until')
                    ->label('Germinación Hasta')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('plantula_since')
                    ->label('Plántula Desde')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('plantula_until')
                    ->label('Plántula Hasta')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('vegetative_since')
                    ->label('Vegetativa Desde')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('vegetative_until')
                    ->label('Vegetativa Hasta')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('flowering_since')
                    ->label('Floración Desde')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('flowering_until')
                    ->label('Floración Hasta')
                    ->sortable()
                    ->searchable(),
                
                // Parámetros clave
                TextColumn::make('irrigation')
                    ->label('Irrigación Sugerida (ml)')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('rest_pruning')
                    ->label('Descanso Entre Podas (días)')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('rest_fert')
                    ->label('Descanso Entre Fertilizaciones (días)')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('stop_fert')
                    ->label('Parar Fertilización Antes de Corte (días)')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('created_at')
                    ->label('Fecha de Creación')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCropPlans::route('/'),
            'create' => Pages\CreateCropPlan::route('/create'),
            'edit' => Pages\EditCropPlan::route('/{record}/edit'),
        ];
    }
}
