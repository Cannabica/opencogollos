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
use Illuminate\Support\Facades\Log;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Carbon\Carbon;
use Filament\Support\Enums\FontWeight;
use Filament\Tables\Columns\Layout\Stack;
use Filament\Tables\Columns\ViewColumn;
use Filament\Forms\Components\Textarea;

class PlantsResource extends Resource
{
    protected static ?string $model = Plant::class;


    protected static ?string $navigationIcon = 'heroicon-o-sun';
    protected static ?int $navigationSort = 3;
    protected static ?string $navigationGroup = 'Plantas';

    protected static ?string $tenantOwnershipRelationshipName = 'indoor';

    protected const SOIL_ENRICHMENT_OPTIONS = [
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
    ];

    protected const BASE_FLOOR_OPTIONS = [
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
    ];

    protected static function getSoilEnrichmentOptions(): array
    {
        $options = static::SOIL_ENRICHMENT_OPTIONS;
        return array_combine(range(0, count($options) - 1), $options);
    }

    protected static function getBaseFloorOptions(): array
    {
        $options = static::BASE_FLOOR_OPTIONS;
        return array_combine(range(0, count($options) - 1), $options);
    }

    protected static function loadSoilEnrichmentValues($record): array
    {


        $state = is_array($record->soil_enrichment)
            ? $record->soil_enrichment
            : json_decode($record->soil_enrichment ?? '[]', true);
        
        $loaded = [];
        foreach ((array)$state as $value) {
            if (is_numeric($value) && isset(static::SOIL_ENRICHMENT_OPTIONS[intval($value)])) {
                $loaded[] = intval($value);
            }
        }
        
        Log::debug('Loaded soil enrichment values:', [
            'record_id' => $record->id,
            'raw_state' => $state,
            'processed_indices' => $loaded
        ]);
        
        return $loaded;
    }

    protected static function loadBaseFloorValues($record): array
    {
        if (!$record->exists) {
            return [];
        }

        $state = is_array($record->base_floor)
            ? $record->base_floor
            : json_decode($record->base_floor ?? '[]', true);
        
        $loaded = [];
        foreach ((array)$state as $value) {
            if (is_numeric($value) && isset(static::BASE_FLOOR_OPTIONS[intval($value)])) {
                $loaded[] = intval($value);
            }
        }
        
        Log::debug('Loaded base floor values:', [
            'record_id' => $record->id,
            'raw_state' => $state,
            'processed_indices' => $loaded
        ]);
        
        return $loaded;
    }

    protected static function prepareSoilEnrichmentForStorage($state): string
    {
        if (!is_array($state)) {
            $state = [];
        }

        $indices = [];
        foreach ((array)$state as $value) {
            if (is_numeric($value)) {
                // Already an index - validate it exists in options
                if (isset(static::SOIL_ENRICHMENT_OPTIONS[intval($value)])) {
                    $indices[] = intval($value);
                }
            } elseif (is_string($value)) {
                // Legacy string value - convert to index
                $index = array_search($value, static::SOIL_ENRICHMENT_OPTIONS);
                if ($index !== false) {
                    $indices[] = $index;
                }
            }
        }

        // Store as JSON array of integers
        return json_encode(array_values(array_unique($indices)));
    }

    protected static function prepareBaseFloorForStorage($state): string
    {
        if (!is_array($state)) {
            $state = [];
        }

        $indices = [];
        foreach ((array)$state as $value) {
            if (is_numeric($value)) {
                // Already an index - validate it exists in options
                if (isset(static::BASE_FLOOR_OPTIONS[intval($value)])) {
                    $indices[] = intval($value);
                }
            } elseif (is_string($value)) {
                // Legacy string value - convert to index
                $index = array_search($value, static::BASE_FLOOR_OPTIONS);
                if ($index !== false) {
                    $indices[] = $index;
                }
            }
        }

        // Store as JSON array of integers
        return json_encode(array_values(array_unique($indices)));
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->whereHas('indoor', function ($query) {
                $query->where('tenant_id', auth()->user()->tenant_id);
            });
    }

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
                                return Indoor::where('tenant_id', auth()->user()->tenant_id)
                                    ->pluck('name', 'id');
                            })
                            ->default(function () {
                                if (Indoor::where('tenant_id', auth()->user()->tenant_id)->count() == 1) {
                                    return Indoor::where('tenant_id', auth()->user()->tenant_id)->first()->id;
                                }
                            })
                            ->required()
                            ->searchable(),

                        TextInput::make('name')
                            ->label(__('Name'))
                            ->required(),

                        Select::make('seed_id')
                            ->label(__('Seed Type'))
                            ->searchable()
                            ->options(function () {
                                return Seed::where(function($query) {
                                    $query->whereNull('tenant_id')
                                          ->orWhere('tenant_id', auth()->user()->tenant_id);
                                })->pluck('name', 'id');
                            })
                            ->required()
                            ->live()
                            ->afterStateUpdated(function ($state, Forms\Set $set) {
                                if ($state) {
                                    $seed = Seed::find($state);
                                    if ($seed) {
                                        $set('seed_details', "Nombre: {$seed->name}\n" .
                                            "Tipo: {$seed->seed_type}\n" .
                                            "THC: {$seed->ratio_thc}% - CBD: {$seed->ratio_cbd}%\n" .
                                            "Tiempo de floración: {$seed->flowering_time} días\n" .
                                            "Proveedor: {$seed->provider}"
                                        );
                                    }
                                } else {
                                    $set('seed_details', null);
                                }
                            })
                            ->afterStateHydrated(function ($state, Forms\Set $set) {
                                if ($state) {
                                    $seed = Seed::find($state);
                                    if ($seed) {
                                        $set('seed_details', "Nombre: {$seed->name}\n" .
                                            "Tipo: {$seed->seed_type}\n" .
                                            "THC: {$seed->ratio_thc}% - CBD: {$seed->ratio_cbd}%\n" .
                                            "Tiempo de floración: {$seed->flowering_time} días\n" .
                                            "Proveedor: {$seed->provider}"
                                        );
                                    }
                                }
                            }),

                        Textarea::make('seed_details')
                            ->label(__('Detalles de la Semilla'))
                            ->disabled()
                            ->dehydrated(false)
                            ->columnSpanFull()
                            ->rows(4)
                            ->visible(fn ($get) => filled($get('seed_id'))),

                        Select::make('state')
                            ->label(__('Plant State'))
                            ->options([
                                'Etapa de Germinación' => 'Etapa de Germinación',
                                'Etapa de Plantula' => 'Etapa de Plantula',
                                'Etapa Vegetativa' => 'Etapa Vegetativa',
                                'Etapa Floracion' => 'Etapa Floracion',
                                'Muerta' => 'Muerta'

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
                                'Geotextiles' => 'Geotextiles',
                                'Plásticas' => 'Plásticas',
                                'Bolsones' => 'Bolsones',
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
                                3 => '3L',
                                5 => '5L',
                                7 => '7L',
                                10 => '10L',
                                12 => '12L',
                                15 => '15L',
                                20 => '20L',
                                30 => '30L',
                                40 => '40L',
                                50 => '50L',
                                75 => '75L',
                            ]),
                    ]),

                Section::make(__('Base Floor'))
                    ->schema([

                        CheckboxList::make('base_floor')
                            ->label(__('Base Floor'))
                            ->options(static::getBaseFloorOptions())
                            ->columns(3)
                            ->bulkToggleable()
                            ->loadStateFromRelationshipsUsing(function ($record) {
                                $values = static::loadBaseFloorValues($record);
                                Log::debug('Loaded base_floor values:', [
                                    'record_id' => $record->id,
                                    'values' => $values
                                ]);
                                return $values;
                            })
                            ->afterStateUpdated(function ($state, Forms\Set $set) {
                                Log::debug('Raw soil_enrichment state before processing:', ['state' => $state]);
                                
                                // Handle both array and JSON string inputs
                                if (is_string($state)) {
                                    $state = json_decode($state, true) ?? [];
                                }
                                
                                if (!is_array($state)) {
                                    $state = [];
                                }
                                
                                $encoded = static::prepareBaseFloorForStorage($state);
                                $set('base_floor', $encoded);
                                
                                Log::debug('Processed base_floor state:', [
                                    'processed_state' => $state,
                                    'encoded' => $encoded
                                ]);
                                
                                Log::debug('Base floor processing:', [
                                    'raw_state' => $state,
                                    'encoded' => $encoded,
                                    'options' => static::BASE_FLOOR_OPTIONS
                                ]);
                            })
                            ->default([]),

                    ]),

                Section::make(__('Soil Enrichment'))
                    ->schema([
                        CheckboxList::make('soil_enrichment')
                            ->label(__('Soil Enrichment'))
                            ->options(static::getSoilEnrichmentOptions())
                            ->columns(3)
                            ->bulkToggleable()
                            ->live()
                            ->loadStateFromRelationshipsUsing(function ($record) {
                                $values = static::loadSoilEnrichmentValues($record);
                                Log::debug('Loaded soil_enrichment values:', [
                                    'record_id' => $record->id,
                                    'values' => $values
                                ]);
                                return $values;
                            })
                            ->afterStateUpdated(function ($state, Forms\Set $set) {
                                Log::debug('Raw soil_enrichment state before processing:', ['state' => $state]);
                                
                                // Skip processing if empty array (likely from form reset)
                                if (empty($state)) {
                                    return;
                                }
                                
                                // Handle both array and JSON string inputs
                                if (is_string($state)) {
                                    $state = json_decode($state, true) ?? [];
                                }
                                
                                if (!is_array($state)) {
                                    $state = [];
                                }
                                
                                $encoded = static::prepareSoilEnrichmentForStorage($state);
                                $set('soil_enrichment', $encoded);
                                
                                Log::debug('Processed soil_enrichment state:', [
                                    'processed_state' => $state,
                                    'encoded' => $encoded
                                ]);
                            })
                            ->dehydrateStateUsing(fn ($state) => $state) // Prevent double processing
                            ->options(static::getSoilEnrichmentOptions())
                            ->loadStateFromRelationshipsUsing(function ($record) {
                                if (!$record->exists) {
                                    return [];
                                }
                                $values = static::loadSoilEnrichmentValues($record);
                                Log::debug('Loaded soil_enrichment values:', [
                                    'record_id' => $record->id,
                                    'values' => $values
                                ]);
                                return $values;
                            })
                            ->default([])
                    ])
                ]);

        return $form;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([

                Stack::make([
                    ViewColumn::make('plant_card')
                        ->view('filament.tables.columns.plant-card')
                        ->alignCenter()
                        ->state(function ($record) {
                            $stateIcons = [
                                'Etapa de Germinación' => 'heroicon-o-sparkles',
                                'Etapa de Plantula' => 'heroicon-o-arrow-up-circle',
                                'Etapa Vegetativa' => 'heroicon-o-sun',
                                'Etapa Floracion' => 'heroicon-o-star',
                                'Muerta' => 'heroicon-o-x-circle',
                            ];

                            $stateBadgeClass = strtolower(str_replace(['Etapa de ', 'Etapa '], '', $record->state));
                            $stateBadgeClass = str_replace(' ', '-', $stateBadgeClass);

                            return [
                                'stateIcon' => $stateIcons[$record->state] ?? 'heroicon-o-question-mark-circle',
                                'stateBadgeClass' => $stateBadgeClass,
                                'daysInState' => now()->diffInDays($record->actions()
                                    ->where('action_type_id', 7)
                                    ->orderByDesc('action_date')
                                    ->first()?->action_date ?? $record->germination_date),
                                'totalDays' => now()->diffInDays($record->germination_date),
                                'actionsCount' => $record->actions()->count(),
                                'pruningsCount' => $record->actions()->where('action_type_id', 2)->count(),
                            ];
                        })
                    ->searchable([
                        'name',
                        
                    ])
                ])
                ->space(2),
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
                SelectFilter::make('indoor_id')
                    ->label(__('Indoor'))
                    ->options(function () {
                        return Indoor::pluck('name', 'id');
                    }),
                Filter::make('Plantas Muertas')
                    ->toggle()
                    ->query(fn (Builder $query): Builder => $query->where('state', '!=', 'muerta'))
                    ->default(true) // Oculta "Muerta" por defecto
                    ->label(__('Ocultar Plantas Muertas')),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
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
