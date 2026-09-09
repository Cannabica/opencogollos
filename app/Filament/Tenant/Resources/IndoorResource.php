<?php

namespace App\Filament\Tenant\Resources;

use App\Filament\Tenant\Resources\IndoorResource\Pages;
use App\Filament\Tenant\Resources\IndoorResource\RelationManagers;
use App\Models\Indoor;
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
use Filament\Forms\Components\Textarea;

class IndoorResource extends Resource
{
    protected static ?string $model = Indoor::class;

    protected static ?string $navigationIcon = 'heroicon-o-home';
    protected static ?int $navigationSort = 4;
    protected static ?string $navigationGroup = 'Plantas';


    public static function getPluralLabel(): string
    {
        return __('Indoors');
    }
    public static function getLabel(): string
    {
        return __('Indoor');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('name')
                    ->label(__('Name'))
                    ->required(),

                Select::make('crop_plan_id')
                    ->label(__('Crop Plan'))
                    ->relationship(
                        'cropPlan',
                        'name',
                        fn(Builder $query) => $query->where(function ($query) {
                            $query->where('tenant_id', auth()->user()->tenant_id)
                                ->orWhereNull('tenant_id');
                        })
                    )
                    ->preload()
                    ->searchable()
                    ->createOptionForm([
                        TextInput::make('name')
                            ->required(),
                    ])
                    ->required(),


                Fieldset::make(__('Dimensions'))
                    ->schema([

                        TextInput::make('large')
                            ->label(__('Large'))
                            ->suffix('cm')
                            ->numeric()
                            ->required(),

                        TextInput::make('width')
                            ->label(__('Width'))
                            ->numeric()
                            ->suffix('cm')
                            ->required(),

                        TextInput::make('height')
                            ->label(__('Height'))
                            ->numeric()
                            ->suffix('cm')
                            ->required(),

                    ])
                    ->columns(3),

                Hidden::make('tenant_id')
                    ->default(fn() => auth()->user()->tenant_id),

                Section::make(__('light_and_ventilation'))
                    ->description(__('light_and_ventilation_description'))
                    ->columns([
                        'sm' => 2,
                        'xl' => 4,
                    ])
                    ->schema(
                        [
                            Repeater::make(name: 'lamps')
                                ->label(__('Lamps'))
                                ->cloneable()
                                ->schema([

                                    TextInput::make('power')
                                        ->label(__('Power'))
                                        ->numeric()
                                        ->suffix('W')
                                        ->columnSpan(1)
                                        ->required(),

                                    Select::make('technology')
                                        ->label(__('Technology'))
                                        ->options([
                                            'led' => 'Led',
                                            'sodio' => 'Sodio',
                                        ])
                                        ->columnSpan(1)
                                        ->required(),

                                    TextInput::make('coverage_area')
                                        ->label(__('Coverage area'))
                                        ->suffix('m2')
                                        ->columnSpan(1)
                                        ->numeric(),

                                    Textarea::make('observations')
                                        ->label(label: __('Observaciones'))
                                        ->placeholder(__('lamp_observations_placeholder'))
                                        ->columnSpanFull(),
                                ])
                                ->minItems(count: 1)
                                ->columns(3)
                                ->columnSpan(2),

                            Repeater::make('fans')
                                ->label(__(key: 'Fans'))
                                ->schema([

                                    TextInput::make('inches')
                                        ->label(__('Inches'))
                                        ->numeric()
                                        ->required(),

                                ])
                                ->cloneable()
                                ->columnSpan(2),
                        ]
                    ),

                Section::make(__('Additional equipment'))
                    ->description('')
                    ->schema([

                        Checkbox::make('hygometer')
                            ->label(__('Tengo higrómetro para medir temperatura y humedad')),

                        Checkbox::make('humidifier')
                            ->label(__('Tengo algún humidificador')),

                    ]),

                Hidden::make('tenant_id')
                    ->default(fn() => auth()->user()->tenant_id),

                Section::make(__('Automatic irrigation equipment'))
                    ->description('')
                    ->schema([
                        TextInput::make('peak_quantity')
                            ->label(__('Peak Quantity'))
                            ->numeric(),

                        TextInput::make('scheduled_time')
                            ->label(__('Scheduled Time'))
                            ->numeric(),

                        TextInput::make('times_a_day')
                            ->label(__('Times a day'))
                            ->numeric(),

                        CheckboxList::make('scheduled_days')
                            ->label(__('Scheduled days'))
                            ->options([
                                'lunes' => 'Lunes',
                                'martes' => 'Martes',
                                'miércoles' => 'Miércoles',
                                'jueves' => 'Jueves',
                                'viernes' => 'Viernes',
                                'sábado' => 'Sábado',
                                'domingo' => 'Domingo',
                            ])
                            ->columns(3),

                    ]),


            ]);
    }
    public static function table(Table $table): Table
    {
        return $table
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->icon('heroicon-o-plus')
                    ->button()
                    ->size('sm'),
            ])
            ->contentGrid([
                'default' => 1,
                'sm' => 1,
                'md' => 2,
                'lg' => 2,
            ])
            ->columns([
                Tables\Columns\Layout\Stack::make([
                    Tables\Columns\Layout\Panel::make([
                        // Información básica
                        TextColumn::make('name')
                            ->label(__('Name'))
                            ->icon('heroicon-o-building-office-2')
                            ->size(TextColumn\TextColumnSize::Large)
                            ->weight('bold')
                            ->searchable()
                            ->sortable()
                            ->alignCenter()
                            ->extraAttributes(['class' => 'py-3 text-primary-600']),

                        // Estadísticas principales
                        Tables\Columns\Layout\Grid::make(['default' => 1,'sm' => 2, 'md' => 2])
                            ->schema([
                                TextColumn::make('plants_count')
                                    ->label(__('Plants in Indoor'))
                                    ->icon('heroicon-o-archive-box-arrow-down')
                                    ->counts('plants')
                                    ->badge()
                                    ->color('success')
                                    ->size(TextColumn\TextColumnSize::Large)
                                    ->alignCenter(),

                                TextColumn::make('cropPlan.name')
                                    ->label(__('Active Plan'))
                                    ->icon('heroicon-o-clipboard-document-check')
                                    ->badge()
                                    ->color('primary')
                                    ->size(TextColumn\TextColumnSize::Large)
                                    ->alignCenter(),
                            ])
                            ->extraAttributes(['class' => 'gap-3 py-2']),



                        // Sección de equipamiento
                        Tables\Columns\Layout\Grid::make(['default' => 1, 'sm' => 2])
                            ->schema([
                                // Especificaciones técnicas
                               
                                // Sistema de iluminación
                                TextColumn::make('lighting_system')
                                    ->label(__('Lighting System'))
                                    ->icon('heroicon-o-sun')
                                    ->state(function ($record): string {
                                        if (empty($record->lamps)) {
                                            return __('No lighting system installed');
                                        }

                                        $lamps = collect($record->lamps)->map(function ($lamp) {
                                            $specs = "{$lamp['power']}W {$lamp['technology']}";
                                            return $specs;
                                        });

                                        return $lamps->join(", ");
                                    })
                                    ->listWithLineBreaks()
                                    ->extraAttributes(['class' => 'indoor-info-section']),

                                // Sistema de ventilación
                                TextColumn::make('ventilation_system')
                                    ->label(__('Ventilation System'))
                                    ->icon('heroicon-o-arrow-path')
                                    ->state(function ($record): string {
                                        if (empty($record->fans)) {
                                            return __('No ventilation system installed');
                                        }

                                        // Agrupar ventiladores por tamaño
                                        $groupedFans = collect($record->fans)
                                            ->groupBy('inches')
                                            ->map(function ($fans, $inches) {
                                            $count = count($fans);
                                            return "{$inches}\"";
                                        })
                                            ->values()
                                            ->join(", ");

                                        return __('Fans: :fans', ['fans' => $groupedFans]);
                                    })
                                    ->listWithLineBreaks()
                                    ->extraAttributes(['class' => 'indoor-info-section']),
                            ])
                            ->extraAttributes(['class' => 'gap-3 py-2']),

                        // Sistemas de control y monitoreo
                        Tables\Columns\Layout\Grid::make(['default' => 1, 'sm' => 2])
                            ->schema([
                                TextColumn::make('specifications')
                                ->label(__('Technical Specifications'))
                                ->icon('heroicon-o-cube')
                                ->state(function ($record): string {
                                    return "Dimensiones: {$record->large}×{$record->width}×{$record->height}";
                                })
                                ->extraAttributes(['class' => 'indoor-info-section']),

                                TextColumn::make('monitoring_systems')
                                    ->label(__('Control Systems'))
                                    ->icon('heroicon-o-chart-bar-square')
                                    ->state(function ($record): string {
                                        $systems = [];
                                        if ($record->hygometer)
                                            $systems[] = "Higrometro";
                                        if ($record->humidifier)
                                            $systems[] = "Humidificador";

                                        return empty($systems)
                                            ? __('No control systems installed')
                                            : implode(", ", $systems);
                                    })
                                    ->listWithLineBreaks()
                                    ->extraAttributes(['class' => 'indoor-info-section']),


                            ])
                            ->extraAttributes(['class' => 'gap-3 py-2']),
                    ])
                        ->collapsible(false),
                ]),
            ])
            ->defaultSort('name', 'asc')
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->icon('heroicon-o-eye')
                    ->button()
                    ->size('sm')
                    ->color('secondary'),
                Tables\Actions\EditAction::make()
                    ->icon('heroicon-o-pencil-square')
                    ->button()
                    ->size('sm'),
                Tables\Actions\DeleteAction::make()
                    ->icon('heroicon-o-trash')
                    ->button()
                    ->size('sm')
                    ->color('accent'),
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
            'index' => Pages\ListIndoors::route('/'),
            'create' => Pages\CreateIndoor::route('/create'),
            'edit' => Pages\EditIndoor::route('/{record}/edit'),
            'view' => Pages\ViewIndoor::route('/{record}'),
        ];
    }
}
