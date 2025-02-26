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

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-group';

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
                            ->label(__('Tengo higometro para medir temperatura y humedad')),

                        Checkbox::make('humidifier')
                            ->label(__('Tengo algún humidificador')),

                    ]),

                Hidden::make('tenant_id')
                    ->default(fn () => auth()->user()->tenant_id),

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

                    ])

            ]);
    }
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label(__('Name')),

                // Conteo de Plantas
                TextColumn::make('plants_count')
                    ->label(__('Plants'))
                    ->counts('plants')
                    ->sortable(),

                // Dimensiones
                TextColumn::make('dimensions')
                    ->label(__('Dimensions'))
                    ->state(function (Indoor $record): string {
                        return "{$record->width}x{$record->large} cm";
                    })
                    ->searchable(false),

                // Lámparas
                TextColumn::make('lamps_info')
                    ->label(__('Lamps'))
                    ->limit(30)
                    ->state(function (Indoor $record): string {
                        if (empty($record->lamps))
                            return 'No lamps';

                        $lampsCount = count($record->lamps);
                        $lampDetails = collect($record->lamps)
                            ->map(function ($lamp) {
                                return "{$lamp['power']}w {$lamp['technology']}";
                            })
                            ->join(', ');

                        return $lampsCount . ' ' . str($lampsCount === 1 ? 'lámpara' : 'lámparas') . ' (' . $lampDetails . ')';
                    })
                    ->searchable(false)
                    ->wrap()
                    ->tooltip(__('Lamp info tooltip')),

                // Ventiladores
                TextColumn::make('fans_info')
                    ->label(__('Fans'))
                    ->state(function (Indoor $record): string {
                        if (empty($record->fans))
                            return 'No fans';

                        $fansCount = count($record->fans);
                        $fansDetails = collect($record->fans)
                            ->map(function ($fan) {
                                return "{$fan['inches']}\"";
                            })
                            ->join(', ');

                        return $fansCount . ' ' . str($fansCount === 1 ? 'ventilador' : 'ventiladores') . ' (' . $fansDetails . ')';
                    })
                    ->searchable(false)
                    ->tooltip('Fan sizes in inches'),
            ])
            ->filters([
                //
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
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListIndoors::route('/'),
            'create' => Pages\CreateIndoor::route('/create'),
            'edit' => Pages\EditIndoor::route('/{record}/edit'),
        ];
    }
}
