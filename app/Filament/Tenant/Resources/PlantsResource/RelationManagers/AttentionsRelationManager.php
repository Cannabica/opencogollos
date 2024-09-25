<?php

namespace App\Filament\Tenant\Resources\PlantsResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Models\Attention;
use App\Models\AttentionType;
use Filament\Forms\Get;

class AttentionsRelationManager extends RelationManager
{
    protected static string $relationship = 'attentions';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('attention_type_id')
                    ->label(__('Attention Type'))
                    ->options(
                        AttentionType::pluck('name', 'id')->toArray()
                    )
                    ->reactive()
                    ->required(),

                Forms\Components\Placeholder::make('Attention')
                    ->label(__('Attention'))
                    ->content(__('Please select an attention type first'))
                    ->visible(fn(Get $get) => $get('attention_type_id') == null),

                Forms\Components\Section::make('Irrigation')
                    ->label(__('Irrigation'))
                    ->schema([
                        Forms\Components\DateTimePicker::make('data.irrigation.irrigation_date')
                            ->label(__('Irrigation Date'))
                    ])
                    ->visible(fn(Get $get) => $get('attention_type_id') == 1),

                Forms\Components\Section::make('Pruning')
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
                    ->visible(fn(Get $get) => $get('attention_type_id') == 2),

                Forms\Components\Section::make('Product Application')
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
                    ->visible(fn(Get $get) => $get('attention_type_id') == 3),

                Forms\Components\Section::make('Transplant')
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
                    ->visible(fn(Get $get) => $get('attention_type_id') == 4),

                Forms\Components\Section::make('Death')
                    ->label(__('Death'))
                    ->schema([
                        Forms\Components\Textarea::make('data.death.observation')
                            ->label(__('Observation'))
                    ])
                    ->visible(fn(Get $get) => $get('attention_type_id') == 6),
                ])
            ->columns(1);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('attention_type_id')
            ->columns([
                Tables\Columns\TextColumn::make('attention_type.name')
                    ->label(__('Attention Type')),
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
                    ->modalHeading(__('Edit attention')),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
