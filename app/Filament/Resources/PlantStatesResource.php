<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PlantStatesResource\Pages;
use App\Filament\Resources\PlantStatesResource\RelationManagers;
use App\Models\PlantState;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Section;
use App\Models\ActionType;

class PlantStatesResource extends Resource
{
    protected static ?string $model = PlantState::class;

    protected static ?string $navigationIcon = 'heroicon-o-presentation-chart-line';

    public static function getPluralLabel(): string
    {
        return __('Plant States');
    }
    public static function getLabel(): string
    {
        return __('Plant State');
    }

    protected static bool $shouldRegisterNavigation = false;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([

                    TextInput::make('name')
                        ->label(__('Name'))
                        ->required(),

                    Section::make('Etapa Comprendida')
                        ->schema([
                            TextInput::make('days_since')
                                ->label(__('Days Since'))
                                ->numeric()
                                ->integer()
                                ->minValue(0)
                                ->required(),
        
                            TextInput::make('days_until')
                                ->label(__('Days Until'))
                                ->numeric()
                                ->integer()
                                ->minValue(0)
                                ->required(),
                        ]),

                    Section::make('Horas de luz')
                        ->schema([
                            TextInput::make('min_daylight_hours')
                                ->label(__('Minimum daylight hours'))
                                ->numeric()
                                ->required(),

                            TextInput::make('max_daylight_hours')
                                ->label(__('Maximum daylight hours'))
                                ->numeric()
                                ->required(),
                        ]),

                    Section::make('Humedad')
                        ->schema([
                            TextInput::make('min_humidity')
                                ->label(__('Minimum humidity'))
                                ->numeric()
                                ->required(),

                            TextInput::make('max_humidity')
                                ->label(__('Maximum humidity'))
                                ->numeric()
                                ->required(),
                        ]),

                Repeater::make('actions')
                    ->label(__('Actions'))
                      // Si tienes relación con otro modelo de Acciones
                    ->schema([
                        Select::make('action_type_id')
                            ->label('Tipo de Acción')
                            ->options(ActionType::all()->pluck('name', 'id')) // Obtén todas las acciones disponibles
                            //->searchable()
                            ->required(),
                        TextInput::make('add_information')
                            ->label(__('Additional Information'))
                            ->required(),
                    ])
                    ->minItems(1)
                    ->maxItems(10)
                    ->columns(1)
            ])->columns(1);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->label('Nombre'),
                Tables\Columns\TextColumn::make('days_since')->label('Días desde'),
                Tables\Columns\TextColumn::make('days_until')->label('Días hasta'),
                Tables\Columns\TextColumn::make('min_daylight_hours')->label('Horas de luz mínimo'),
                Tables\Columns\TextColumn::make('max_daylight_hours')->label('Horas de luz máximo'),
                Tables\Columns\TextColumn::make('min_humidity')->label('Humedad mínimo'),
                Tables\Columns\TextColumn::make('max_humidity')->label('Humedad máximo'),
                //Tables\Columns\TextColumn::make('actions')->label('Acciones'),
           
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
            'index' => Pages\ListPlantStates::route('/'),
            'create' => Pages\CreatePlantStates::route('/create'),
            'edit' => Pages\EditPlantStates::route('/{record}/edit'),
        ];
    }
}
