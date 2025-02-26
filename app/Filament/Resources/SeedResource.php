<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SeedResource\Pages;
use App\Filament\Resources\SeedResource\RelationManagers;
use App\Models\Seed;
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

class SeedResource extends Resource
{
    protected static ?string $model = Seed::class;
    protected static ?string $navigationIcon = 'heroicon-o-archive-box-arrow-down';


    public static function getPluralLabel(): string
    {
        return __('Seeds');
    }
    public static function getLabel(): string
    {
        return __('Seed');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([

                TextInput::make('name')
                    ->label(__('Name'))
                    ->required(),

                Select::make('seed_type')
                    ->label(__('Tipo de semilla'))
                    ->options([

                        'Fotoperiodica feminizada' => 'Fotoperiodica feminizada',
                        'Fotoperiodica regular' => 'Fotoperiodica regular',
                        'Automatica' => 'Automatica'
                    ])
                    ->required(),

                TextInput::make('flowering_time')
                    ->label(__('Tiempo de floración en semanas'))
                    ->numeric()
                    ->required(),

                TextInput::make('ratio_thc')
                    ->label(__('Ratio THC (en %)'))
                    ->numeric()
                    ->required(),

                TextInput::make('ratio_cbd')
                    ->label(__('Ratio CBD (en %)'))
                    ->numeric()
                    ->required(),

            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label(__('Name')),
                TextColumn::make('seed_type')
                    ->label(__('Seed Type')),
                TextColumn::make('flowering_time')
                    ->label(__('Flowering Time')),
                TextColumn::make('ratio_thc')
                    ->label(__('Ratio THC')),
                TextColumn::make('ratio_cbd')
                    ->label(__('Ratio CBD')),
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
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSeeds::route('/'),
            'create' => Pages\CreateSeed::route('/create'),
            'edit' => Pages\EditSeed::route('/{record}/edit'),
        ];
    }
}
