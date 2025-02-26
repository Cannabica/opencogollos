<?php

namespace App\Filament\Tenant\Resources\PlantsResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Models\Action;
use App\Models\ActionType;
use Filament\Forms\Get;

class ActionsRelationManager extends RelationManager
{
    protected static string $relationship = 'actions';

    public static function getPluralLabel(): string
    {
        return __('Action');
    }
    public static function getLabel(): string
    {
        return __('Actions');
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('action_type_id')
                    ->label(__('Action Type'))
                    ->options(
                        ActionType::pluck('name', 'id')->toArray()
                    )
                    ->reactive()
                    ->required(),
                Forms\Components\Placeholder::make('Action')
                    ->label(__('Action'))
                    ->content(__('Please select an action type first'))
                    ->visible(fn(Get $get) => $get('action_type_id') == null),
                Forms\Components\Section::make(__('Irrigation'))
                    ->label(__('Irrigation'))
                    ->schema([
                        Forms\Components\DateTimePicker::make('data.irrigation.irrigation_date')
                            ->label(__('Irrigation Date'))
                    ])
                    ->visible(fn(Get $get) => $get('action_type_id') == 1),

                Forms\Components\Section::make(__('Pruning'))
                    ->label(__('Pruning'))
                    ->schema([
                        Forms\Components\Select::make('data.pruning.pruning_type')
                            ->label(__('Pruning Type'))
                            ->options([
                                'topping' => 'Poda apical /topping',
                                'yellowish_tips' => 'Puntas amarillentas secas',
                                'excess' => 'Excedente hojas',
                                'scrog' => 'Realizado SCROG',
                                'lollipoping' => 'Realizado lollipoping',
                                'supercropping' => 'Realizado supercropping',
                            ]),
                    ])
                    ->visible(fn(Get $get) => $get('action_type_id') == 2),

                Forms\Components\Section::make(__('Product Application'))
                    ->label(__('Product Application'))
                    ->schema([
                        Forms\Components\Select::make('data.product_application.application_type')
                            ->label(__('Application Type'))
                            ->options([
                                'vege' => 'Aplicación para vege',
                                'flora' => 'Aplicación para flora',
                                'plantula' => 'Aplicación etapa plantula',
                                'plague' => 'Aplicación anti - plaga',
                                'soap' => 'Lavado de planta con jabon potasico',
                            ]),
                        Forms\Components\Textarea::make('data.product_application.observation')
                            ->label(__('Observation')),
                        Forms\Components\Select::make('data.product_application.iterative_process')
                            ->label(__('¿Es parte de un proceso iterativo?'))
                            ->options([
                                1 => 'Si',
                                0 => 'No',
                            ]),
                    ])
                    ->visible(fn(Get $get) => $get('action_type_id') == 3),

                Forms\Components\Section::make(__('Transplant'))
                    ->label(__('Transplant'))
                    ->schema([
                        Forms\Components\Select::make('data.transplant.new_pot_size')
                            ->label(__('Tamaño de la nueva maceta'))
                            ->options([
                                'N10',
                                'N12',
                                'N14',
                                '3L',
                               ' 5L',
                               ' 7L',
                                '10L',
                                '12L',
                                '15L',
                                '20L',
                                '30L',
                                '40L',
                                '50L',
                                '75L',
                            ])
                    ])
                    ->visible(fn(Get $get) => $get('action_type_id') == 4),

                Forms\Components\Section::make(__('Death'))
                    ->label(__('Death'))
                    ->schema([
                        Forms\Components\Textarea::make('data.death.observation')
                            ->label(__('Observation'))
                    ])
                    ->visible(fn(Get $get) => $get('action_type_id') == 6),
                ])
            ->columns(1);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('action_type_id')
            ->columns([
                Tables\Columns\TextColumn::make('action_type.name')
                    ->label(__('Action Type')),
                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('Register date')),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->modalHeading(__('Edit action')),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
