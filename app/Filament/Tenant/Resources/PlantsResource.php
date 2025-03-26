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

    protected static ?string $tenantOwnershipRelationshipName = 'indoor';

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
