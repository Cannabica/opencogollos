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
use Filament\Forms\Components\Grid;
use Filament\Infolists\Components\TextEntry;
use Filament\Forms\Components\Placeholder;
use Illuminate\Support\HtmlString;




class CropPlanResource extends Resource
{
    protected static ?string $model = CropPlan::class;

    protected static ?string $navigationIcon = 'heroicon-o-beaker';


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
                    ->columns(2)
                    ->schema([
                        TextInput::make('name')
                            ->label('Nombre')
                            ->columnSpan('full')
                            ->required(),

                        TextInput::make('rest_pruning')
                            ->label('Descanso entre podas')
                            ->helperText(__('helper_rest_pruning'))
                            ->numeric(1)
                            ->suffix(__('days'))
                            ->step(1)
                            ->required(),

                        TextInput::make('rest_fert')
                            ->label('Descanso entre fertilizaciones')
                            ->helperText(__('helper_rest_fert'))
                            ->numeric()
                            ->suffix(__('days'))
                            ->step(1)
                            ->required(),

                        TextInput::make('stop_fert')
                            ->label('Dejar de fertilizar antes de fecha de corte')
                            ->helperText(__('helper_stop_fert'))
                            ->numeric()
                            ->suffix(__('days'))
                            ->step(1)
                            ->required(),

                        TextInput::make('irrigation')
                            ->label('Irrigación de maceta sugerida')
                            ->helperText(__('helper_irrigation'))
                            ->suffix('%')
                            ->numeric()
                            ->required(),

                    ]),

                Section::make('Etapa de germinación')
                    ->columns('4')
                    ->schema([
                        Fieldset::make('Período comprendido')
                            ->label('Maximos dias de vida en etapa')
                            ->columnSpan('1')
                            ->schema([
                                TextInput::make('germination_since')
                                    ->label('Primer aviso')
                                    ->suffix('dias')
                                    ->numeric()
                                    ->step(1)
                                    ->columnSpan('2')
                                    ->required(),

                                TextInput::make('germination_until')
                                    ->label('Segundo aviso')
                                    ->numeric()
                                    ->suffix('dias')
                                    ->columnSpan('2')
                                    ->step(1)
                                    ->required(),

                                Placeholder::make('documentation')
                                    ->label('')
                                    ->columnSpan('full')
                                    ->helperText(__('helper_max_days_stage')),
                            ]),

                        Fieldset::make('Parametros ambiente')
                            ->columnSpan('3')
                            ->columns(columns: '2')
                            ->inlineLabel()
                            ->schema([

                                TextInput::make('germination_light')
                                    ->label('Luz encendida')
                                    ->numeric()
                                    ->suffix('hs')
                                    ->required(),

                                TextInput::make('germination_darkness')
                                    ->label('Oscuridad')
                                    ->numeric()
                                    ->suffix('hs')
                                    ->required(),

                                TextInput::make('germination_humidity_since')
                                    ->label('Humedad min')
                                    ->suffix('%')
                                    ->numeric()
                                    ->required(),

                                TextInput::make('germination_humidity_until')
                                    ->label('Humedad max')
                                    ->suffix('%')
                                    ->numeric()
                                    ->required(),

                                TextInput::make('germination_temp_since')
                                    ->label('Temp min')
                                    ->suffix('°C')
                                    ->columnSpan('1')
                                    ->numeric()
                                    ->required(),

                                TextInput::make('germination_temp_until')
                                    ->label('Temp max')
                                    ->suffix('°C')
                                    ->columnSpan('1')
                                    ->numeric()
                                    ->required(),

                                Placeholder::make('documentation')
                                    ->label('')
                                    ->columnSpan('full')
                                    ->helperText(__('helper_ambience_parameters')),

                            ]),

                    ]),


                Section::make('Etapa Plantula')
                    ->columns('4')
                    ->schema([
                        Fieldset::make('Período comprendido')
                            ->label('Maximos dias de vida en etapa')
                            ->columnSpan('1')
                            ->schema([
                                TextInput::make('plantula_since')
                                    ->label('Primer aviso')
                                    ->suffix('dias')
                                    ->numeric()
                                    ->step(1)
                                    ->columnSpan('2')
                                    ->required(),

                                TextInput::make('plantula_until')
                                    ->label('Segundo aviso')
                                    ->numeric()
                                    ->suffix('dias')
                                    ->columnSpan('2')
                                    ->step(1)
                                    ->required(),

                                Placeholder::make('documentation')
                                    ->label('')
                                    ->columnSpan('full')
                                    ->helperText(__('helper_max_days_stage')),
                            ]),

                        Fieldset::make('Parametros ambiente')
                            ->columnSpan('3')
                            ->columns('2')
                            ->inlineLabel()
                            ->schema([

                                TextInput::make('plantula_light')
                                    ->label('Luz encendida')
                                    ->numeric()
                                    ->suffix('hs')
                                    ->required(),

                                TextInput::make('plantula_darkness')
                                    ->label('Oscuridad')
                                    ->numeric()
                                    ->suffix('hs')
                                    ->required(),

                                TextInput::make('plantula_humidity_since')
                                    ->label('Humedad min')
                                    ->suffix('%')
                                    ->numeric()
                                    ->required(),

                                TextInput::make('plantula_humidity_until')
                                    ->label('Humedad max')
                                    ->suffix('%')
                                    ->numeric()
                                    ->required(),

                                TextInput::make('plantula_temp_since')
                                    ->label('Temp min')
                                    ->suffix('°C')
                                    ->columnSpan('1')
                                    ->numeric()
                                    ->required(),

                                TextInput::make('plantula_temp_until')
                                    ->label('Temp max')
                                    ->suffix('°C')
                                    ->columnSpan('1')
                                    ->numeric()
                                    ->required(),

                                Placeholder::make('documentation')
                                    ->label('')
                                    ->columnSpan('full')
                                    ->helperText(__('helper_ambience_parameters')),

                            ]),

                    ]),

                Section::make('Etapa Vegetativa')
                    ->columns('4')
                    ->schema([
                        Fieldset::make('Período comprendido')
                            ->label('Maximos dias de vida en etapa')
                            ->columnSpan('1')
                            ->schema([
                                TextInput::make('vegetative_since')
                                    ->label('Primer aviso')
                                    ->suffix('dias')
                                    ->numeric()
                                    ->step(1)
                                    ->columnSpan('2')
                                    ->required(),

                                TextInput::make('vegetative_until')
                                    ->label('Segundo aviso')
                                    ->numeric()
                                    ->suffix('dias')
                                    ->columnSpan('2')
                                    ->step(1)
                                    ->required(),

                                Placeholder::make('documentation')
                                    ->label('')
                                    ->columnSpan('full')
                                    ->helperText(__('helper_max_days_stage')),
                            ]),

                        Fieldset::make('Parametros ambiente')
                            ->columnSpan('3')
                            ->columns('2')
                            ->inlineLabel()
                            ->schema([

                                TextInput::make('vegetative_light')
                                    ->label('Luz encendida')
                                    ->numeric()
                                    ->suffix('hs')
                                    ->required(),

                                TextInput::make('vegetative_darkness')
                                    ->label('Oscuridad')
                                    ->numeric()
                                    ->suffix('hs')
                                    ->required(),

                                TextInput::make('vegetative_humidity_since')
                                    ->label('Humedad min')
                                    ->suffix('%')
                                    ->numeric()
                                    ->required(),

                                TextInput::make('vegetative_humidity_until')
                                    ->label('Humedad max')
                                    ->suffix('%')
                                    ->numeric()
                                    ->required(),

                                TextInput::make('vegetative_temp_since')
                                    ->label('Temp min')
                                    ->suffix('°C')
                                    ->columnSpan('1')
                                    ->numeric()
                                    ->required(),

                                TextInput::make('vegetative_temp_until')
                                    ->label('Temp max')
                                    ->suffix('°C')
                                    ->columnSpan('1')
                                    ->numeric()
                                    ->required(),

                                Placeholder::make('documentation')
                                    ->label('')
                                    ->columnSpan('full')
                                    ->helperText(__('helper_ambience_parameters')),

                            ]),

                    ]),

                Section::make('Etapa Floración')
                    ->columns('4')
                    ->schema([
                        Fieldset::make('Período comprendido')
                            ->label('Maximos dias de vida en etapa')
                            ->columnSpan('1')
                            ->schema([
                                TextInput::make('flowering_since')
                                    ->label('Primer aviso')
                                    ->suffix('dias')
                                    ->numeric()
                                    ->step(1)
                                    ->columnSpan('2')
                                    ->required(),

                                TextInput::make('flowering_until')
                                    ->label('Segundo aviso')
                                    ->numeric()
                                    ->suffix('dias')
                                    ->columnSpan('2')
                                    ->step(1)
                                    ->required(),

                                Placeholder::make('documentation')
                                    ->label('')
                                    ->columnSpan('full')
                                    ->helperText(__('helper_max_days_stage')),
                            ]),

                        Fieldset::make('Parametros ambiente')
                            ->columnSpan('3')
                            ->columns('2')
                            ->inlineLabel()
                            ->schema([

                                TextInput::make('flowering_light')
                                    ->label('Luz encendida')
                                    ->numeric()
                                    ->suffix('hs')
                                    ->required(),

                                TextInput::make('flowering_darkness')
                                    ->label('Oscuridad')
                                    ->numeric()
                                    ->suffix('hs')
                                    ->required(),

                                TextInput::make('flowering_humidity_since')
                                    ->label('Humedad min')
                                    ->suffix('%')
                                    ->numeric()
                                    ->required(),

                                TextInput::make('flowering_humidity_until')
                                    ->label('Humedad max')
                                    ->suffix('%')
                                    ->numeric()
                                    ->required(),

                                TextInput::make('flowering_temp_since')
                                    ->label('Temp min')
                                    ->suffix('°C')
                                    ->columnSpan('1')
                                    ->numeric()
                                    ->required(),

                                TextInput::make('flowering_temp_until')
                                    ->label('Temp max')
                                    ->suffix('°C')
                                    ->columnSpan('1')
                                    ->numeric()
                                    ->required(),

                                Placeholder::make('documentation')
                                    ->label('')
                                    ->columnSpan('full')
                                    ->helperText(__('helper_ambience_parameters')),

                            ]),

                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->actionsPosition(Tables\Enums\ActionsPosition::BeforeColumns)
            ->columns([
                // Resumen del ciclo de cultivo
                TextColumn::make('name')
                    ->label(label: 'Nombre')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('germination_since')
                    ->label('Periodo Germinación')
                    ->tooltip(__('cropPlan_stages_tooltip'))
                    ->wrapHeader()
                    ->markdown()
                    ->listWithLineBreaks()
                    ->formatStateUsing(function ($state, $record) {
                        $weeksSince = round($record->germination_since / 7, 1);
                        return "{$record->germination_since} " . __('days') .
                            "  __({$weeksSince} " . __('weeks') . ")__ <br>" .
                            "*" . __('alert_in_cant_days') . " {$record->germination_until} " . __('days') . "*";
                    })
                    ->sortable(),

                TextColumn::make('plantula_since')
                    ->label('Periodo Plantula')
                    ->tooltip(__('cropPlan_stages_tooltip'))
                    ->wrapHeader()
                    ->markdown()
                    ->listWithLineBreaks()
                    ->formatStateUsing(function ($state, $record) {
                        $weeksSince = round($record->plantula_since / 7, 1);
                        return "{$record->plantula_since} " . __('days') .
                            "  __({$weeksSince} " . __('weeks') . ")__ <br>" .
                            "*" . __('alert_in_cant_days') . " {$record->plantula_until} " . __('days') . "*";
                    })

                    ->sortable(),

                TextColumn::make('vegetative_since')
                    ->label('Periodo Vegetativo')
                    ->tooltip(__('cropPlan_stages_tooltip'))
                    ->wrapHeader()
                    ->markdown()
                    ->listWithLineBreaks()
                    ->formatStateUsing(function ($state, $record) {
                        $weeksSince = round($record->vegetative_since / 7, 1);
                        return "{$record->vegetative_since} " . __('days') .
                            "  __({$weeksSince} " . __('weeks') . ")__ <br>" .
                            "*" . __('alert_in_cant_days') . " {$record->vegetative_until} " . __('days') . "*";
                    })


                    ->sortable(),

                TextColumn::make('flowering_since')
                    ->label('Periodo Floracion')
                    ->tooltip(__('cropPlan_stages_tooltip'))
                    ->wrapHeader()
                    ->markdown()
                    ->listWithLineBreaks()
                    ->formatStateUsing(function ($state, $record) {
                        $weeksSince = round($record->flowering_since / 7, 1);
                        return "{$record->flowering_since} " . __('days') .
                            "  __({$weeksSince} " . __('weeks') . ")__ <br>" .
                            "*" . __('alert_in_cant_days') . " {$record->flowering_until} " . __('days') . "*";
                    })
                    ->sortable(),

                // Parámetros clave
                TextColumn::make('irrigation')
                    ->label('Irrigación Sugerida')
                    ->sortable()
                    ->wrapHeader()
                    ->tooltip(__('irrigation_suggest_tooltip'))
                    ->suffix('%')
                    ->alignCenter()
                    ->searchable(),

                TextColumn::make('rest_pruning')
                    ->label('Descanso Podas')
                    ->sortable()
                    ->wrapHeader()
                    ->suffix(' ' . __('days'))
                    ->tooltip(__('rest_pruning_tooltip'))
                    ->searchable(),

                TextColumn::make('rest_fert')
                    ->label('Descanso Fertilizaciones')
                    ->sortable()
                    ->wrapHeader()
                    ->suffix(' ' . __('days'))
                    ->tooltip(__('rest_fert_tooltip'))
                    ->searchable(),

                TextColumn::make('stop_fert')
                    ->label('Detener Fertilización')
                    ->sortable()
                    ->wrapHeader()
                    ->suffix(' ' . __('days_before_flowering_date'))
                    ->tooltip(__('stop_fert_tooltip'))
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
            'index' => Pages\ListCropPlans::route('/'),
            'create' => Pages\CreateCropPlan::route('/create'),
            'edit' => Pages\EditCropPlan::route('/{record}/edit'),
        ];
    }
}
