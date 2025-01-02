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
use Filament\Support\Enums\FontWeight;
use Filament\Tables\Columns\Layout\Stack;
use Filament\Tables\Columns\ViewColumn;



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
                    ->columns([
                        'sm' => 1,
                        'xl' => 3,
                    ])
                    ->schema([

                        Select::make('indoor_id')
                            ->label(__('Indoor'))

                            ->options(function () {
                                return Indoor::pluck('name', 'id'); //TODO Scope Tenant
                            })
                            ->default(function () {
                                if (Indoor::count() == 1)
                                    return Indoor::first()->id;
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

                        Select::make('state')
                            ->label(__('Plant State'))
                            ->options([
                                'Etapa de Germinación' => 'Etapa de Germinación',
                                'Etapa de Plantula' => 'Etapa de Plantula',
                                'Etapa Vegetativa' => 'Etapa Vegetativa',
                                'Etapa Floracion' => 'Etapa Floracion',

                            ])
                            ->required(),

                        DatePicker::make('germination_date')
                            ->label(__('Germination Date'))
                            ->required(),

                    ]),

                Section::make(__('Pot and Substrate'))
                    ->columns([
                        'sm' => 1,
                        'xl' => 3,
                    ])
                    ->schema([
                        Select::make('flowerpot')
                            ->label(label: __('Flowerpot'))
                            ->options([
                                'Geotextiles',
                                'Plásticas',
                                'Bolsones'
                            ])
                            ->required(),

                        // TextInput::make('capacity')
                        //     ->label(__('Capacity'))
                        //     ->numeric()
                        //     ->required(),

                        Select::make('capacity')
                            ->label(__('Capacity'))
                            ->required()
                            ->options([
                                'N10' => 'N10',
                                'N12' => 'N12',
                                'N14' => 'N14',
                                '3L' => '3L',
                                '5L' => '5L',
                                '7L' => '7L',
                                '10L' => '10L',
                                '12L' => '12L',
                                '15L' => '15L',
                                '20L' => '20L',
                                '30L' => '30L',
                                '40L' => '40L',
                                '50L' => '50L',
                                '75L' => '75L',
                            ]),
                    ]),

                Section::make(__('Base Floor'))
                    ->schema([

                        CheckboxList::make('base_floor')
                            ->label(__('base_floor_description'))
                            ->columns(3)
                            ->bulkToggleable()
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
                            ->label(__('Soil_Enrichment_description'))
                            ->columns(3)
                            ->bulkToggleable()
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

            // ->columns([
            //     Stack::make([
            //         // Columns
            //     ]),
            // ])
            // ->contentGrid([
            //     'md' => 2,
            //     'xl' => 3,
            // ]);


            ->columns([
                // opcion 1 custom view, muy artesanal todo
                // ViewColumn::make('view')->view('filament.tables.columns.status-plant'),

                //opcion 2, le metemos chimi a la customización de la tabla

                Stack::make([

                    TextColumn::make('name')
                        ->formatStateUsing(function ($state, $record) {
                            return "{$record->name}";
                        })
                        ->alignCenter()
                        ->size(size: TextColumn\TextColumnSize::Large)
                        ->weight(FontWeight::Bold),

                    TextColumn::make('seedType.name')
                        ->formatStateUsing(
                            fn($state, $record) =>
                            $record->seedType?->name
                            ? "Semilla: {$record->seedType->name} ( {$record->seedType->seed_type} ) "
                            : 'No disponible'
                        ),

                    TextColumn::make('germination_date')
                        ->formatStateUsing(
                            callback: fn($state, $record) =>
                            $record->germination_date
                            ? __('days_of_life') . ' ' . now()->diffInDays($record->germination_date)
                            : 'Fecha no disponible'
                        ),

                    // ViewColumn::make('view')->view('filament.tables.columns.status-plant')
                    // ->searchable(),

                    TextColumn::make('seedType.ratio_thc')
                        ->searchable()
                        ->formatStateUsing(
                            fn($state, $record) =>

                            ($record->seedType?->ratio_thc !== null) && ($record->seedType?->ratio_cbd !== null)
                            ? 'THC: ' . $record->seedType->ratio_thc . '% - CBD: ' . $record->seedType->ratio_cbd . '%'
                            : 'No disponible'
                        ),


                    TextColumn::make('state')
                        ->badge()
                        ->color(fn(string $state): string => match ($state) {
                            'Etapa de Germinación' => 'secondary',
                            'Etapa de Plantula' => 'primary',
                            'Etapa Vegetativa' => 'tertiary',
                            'Etapa Floracion' => 'accent',
                            'muerta' => 'gray',
                        }),


                    // TextColumn::make('indoor_id')
                    // ->getStateUsing(fn($record) => __('indoor_id').': '.$record->indoor()->where('indoor_id', $record->indoor_id)),
                    // ->formatStateUsing(
                    //     fn($state, $record) =>
                    //     $record->indoor_id
                    //     $record->actions()->where('action_type_id', 2)->count()
                    //     ? "Indoor: {$record->indoor_id}"
                    //     : 'No disponible'
                    // ),


                    TextColumn::make('indoor_id')
                        ->size(TextColumn\TextColumnSize::ExtraSmall)
                        ->color('success')
                        ->weight(FontWeight::ExtraLight)
                        ->formatStateUsing(function ($state, $record) {
                            // Obtener la última acción de tipo "change_state" asociada a esta planta
                            $lastChangeStateAction = $record->actions()
                                ->where('action_type_id', 7) // ID del tipo "change_state"
                                ->orderByDesc('action_date') // Ordenar por fecha de la acción
                                ->first();
                        
                            // Determinar la base para el cálculo (última acción o fecha de germinación)
                            $baseDate = $lastChangeStateAction?->action_date ?? $record->germination_date;
                        
                            return $baseDate
                                ? 'Esta planta está hace ' . now()->diffInDays($baseDate) . ' días en la misma etapa'
                                : 'No disponible';
                        }),
                                                

                    TextColumn::make('indoor.name')
                        ->label(__('Indoor Name'))
                        ->formatStateUsing(function ($state, $record) {
                            return __('indoor_name') . ' ' . $record->indoor?->name ?? __('Not Available');
                        })
                        ->size(TextColumn\TextColumnSize::Small)
                        ->weight(FontWeight::Light),

                    TextColumn::make('flowerpot')
                        ->formatStateUsing(function ($record) {
                            $flowerpots = ['Geotextiles', 'Plásticas', 'Bolsones'];
                            return __('recipient') . ': ' . $flowerpots[$record->flowerpot] . ' ' . $record->capacity ?? 'Desconocido';
                        }),

                    TextColumn::make('actions_count')
                        ->label('Total de Acciones')
                        ->getStateUsing(fn($record) => __('actions_registered_for_plant') . ': ' . $record->actions()->count()),

                    TextColumn::make('prunings_count')
                        ->label('Podas Realizadas')
                        ->getStateUsing(fn($record) => __('prunes_count_for_plant') . ': ' . $record->actions()->where('action_type_id', 2)->count()),

                ])
                    ->space(2),


                // por defecto la tabla fiera
                // TextColumn::make('name')
                //     ->label(__('Name')),  
                // TextColumn::make('seedType.name')
                //     ->label(__('Seed Type')),   
                // TextColumn::make('germination_date')
                //     ->label(__('Germination Date')),
            ])
            ->contentGrid([
                'md' => 2,
                'xl' => 3,
            ])
            ->filters([
                SelectFilter::make('seed_id')
                    ->label(__('Seed Type'))
                    ->options(function () {
                        return Seed::pluck('name', 'id');
                    }),
                SelectFilter::make('state')
                    ->label(__('Plant State'))
                    ->options([
                        'Etapa de Germinación' => 'Etapa de Germinación',
                        'Etapa de Plantula' => 'Etapa de Plantula',
                        'Etapa Vegetativa' => 'Etapa Vegetativa',
                        'Etapa Floracion' => 'Etapa Floracion',
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
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
            'index' => Pages\ListPlants::route(path: '/'),
            'create' => Pages\CreatePlants::route('/create'),
            'edit' => Pages\EditPlants::route('/{record}/edit'),
        ];
    }
}
