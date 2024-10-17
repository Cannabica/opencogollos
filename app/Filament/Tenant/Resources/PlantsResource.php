<?php

namespace App\Filament\Tenant\Resources;

use App\Filament\Tenant\Resources\PlantsResource\Pages;
use App\Filament\Tenant\Resources\PlantsResource\RelationManagers;
use App\Filament\Tenant\Resources\PlantsResource\RelationManagers\ActionsRelationManager;
use App\Models\Plant;
use App\Models\Indoor;
use App\Models\Seed;
use App\Models\Batch;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;

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
                Forms\Components\TextInput::make('name')
                    ->label(__('Name'))
                    ->required(),
                Forms\Components\Select::make('seed_id')
                    ->label(__('Seed Type'))
                    ->options(function () {
                        return Seed::pluck('name', 'id');
                    })
                    ->required(),
                Forms\Components\Select::make('indoor_id')
                    ->label(__('Indoor'))
                    ->options(function () {
                        // Filtrar las opciones por el tenant_id del usuario autenticado
                        $tenantId = Auth::user()->tenant_id;
                        return Indoor::where('tenant_id', $tenantId)
                            ->pluck('name', 'id');
                    })
                    ->required(),
                Forms\Components\Select::make('etapa')
                    ->label(__('Etapa'))
                    ->options([
                        'Germinación',
                        'Plántula',
                        'Vegetativa',
                        'Floración',
                        'Cosecha y curado',
                    ])
                    ->required(),
                Forms\Components\Select::make('pot_type')
                    ->label(__('Pot Type'))
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
                    ->required(),
                Forms\Components\DateTimePicker::make('germination_date')
                    ->label(__('Germination Date'))
                    ->required(),
                Forms\Components\DateTimePicker::make('planting_date')
                    ->label(__('Planting Date'))
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label(__('Name')),
                Tables\Columns\TextColumn::make('seedType.name')
                    ->label(__('Seed Type')),
                Tables\Columns\TextColumn::make('indoor.name')
                    ->label(__('Indoor')),
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
