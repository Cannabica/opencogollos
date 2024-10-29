<?php

namespace App\Filament\Tenant\Resources;

use App\Filament\Tenant\Resources\PlantsResource\Pages;
use App\Filament\Tenant\Resources\PlantsResource\RelationManagers;
use App\Filament\Tenant\Resources\PlantsResource\RelationManagers\ActionsRelationManager;
use App\Models\Plant;
use App\Models\Indoor;
use App\Models\Seed;
use App\Models\PlantState;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Fieldset;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;

class PlantsResource extends Resource
{
    protected static ?string $model = Plant::class;

    protected static ?string $navigationIcon = 'heroicon-o-sun';

    public static function getPluralLabel(): string
    {
        return __('Plants');
    }
    public static function getLabel(): string
    {
        return __('Plant');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([

                Section::make(__('Basic Data'))
                    ->schema([

                        Select::make('indoor_id')
                            ->label(__('Indoor'))
                            ->options(function () {
                                return Indoor::pluck('name', 'id'); //TODO Scope Tenant
                            })
                            ->default(function() {
                                if(Indoor::count() == 1) return Indoor::first()->id;
                                return null;
                            })
                            ->required(),

                        TextInput::make('name')
                            ->label(__('Name'))
                            ->required(),

                        Select::make('seed_id')
                            ->label(__('Seed Type'))
                            ->options(function () {
                                return Seed::pluck('name', 'id');
                            })
                            ->required(),

                        Select::make('plant_state_id')
                            ->label(__('Plant State'))
                            ->options(function () {
                                return PlantState::pluck('name', 'id'); 
                            })
                            ->required(),

                        DatePicker::make('germination_date')
                            ->label(__('Germination Date'))
                            ->required(),

                    ]),

                Section::make(__('Pot and Substrate'))
                    ->schema([

                        Select::make('flowerpot')
                            ->label(__('Flowerpot'))
                            ->options([
                                'Geotextiles',
                                'Plásticas',
                                'Bolsones',
                                'N10',
                                'N12',
                                'N14',
                                '3L',
                                '5L',
                                '7L',
                                '10L',
                                '12L',
                                '15L',
                                '20L',
                                '30L',
                                '40L',
                                '50L',
                                '75L',
                            ])
                            ->required(),

                        TextInput::make('capacity')
                            ->label(__('Capacity'))
                            ->numeric()
                            ->required(),
                    ]),

                Section::make(__('Base Floor'))
                    ->schema([

                        CheckboxList::make('base_floor')
                            ->options([
                                'Turba',
                                'Guano',
                                'Estiércol',
                                'Polvo de roca',
                                'Arena',
                                'Fibra de coco',
                                'Abono naturales',
                                'Corteza de pino',
                                'Perlita',
                                'Vermiculita'
                            ]),

                    ]),

                Section::make(__('Soil Enrichment'))
                    ->schema([

                        CheckboxList::make('soil_enrichment')
                            ->options([
                                'Posos de café y/o te',
                                'Cascaras de huevo',
                                'Humus de lombriz',
                                'Pieles de frutas y verd',
                                'Abono',
                                'Fibra de coco',
                                'Perlita',
                                'Vermiculita',
                                'Arena',
                                'Harina de huesos',
                                'Harina de sangre',
                                'Roca fosfórica',
                                'Cal'
                            ])

                    ]),

            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label(__('Name')),  
                TextColumn::make('seedType.name')
                    ->label(__('Seed Type')),   
                TextColumn::make('germination_date')
                    ->label(__('Germination Date')),
            ])
            ->filters([
                SelectFilter::make('seed_id')
                    ->label(__('Seed Type'))
                    ->options(function () {
                        return Seed::pluck('name', 'id');
                    }),
                SelectFilter::make('plant_state_id')
                    ->label(__('Plant State'))
                    ->options(function () {
                        return PlantState::pluck('name', 'id'); 
                    }),
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
            ActionsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPlants::route('/'),
            'create' => Pages\CreatePlants::route('/create'),
            'edit' => Pages\EditPlants::route('/{record}/edit'),
        ];
    }
}
