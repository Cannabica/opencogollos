<?php

namespace App\Filament\Tenant\Resources;

use App\Filament\Tenant\Resources\SeedResource\Pages;
use App\Filament\Tenant\Resources\SeedResource\RelationManagers;
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
use Filament\Tables\Columns\BadgeColumn;


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
                    ->label(__('Tiempo de floración'))
                    ->numeric()
                    ->suffix(label: __('weeks'))
                    ->required(),

                TextInput::make('ratio_thc')
                    ->label(__('Ratio THC'))
                    ->numeric()
                    ->suffix(label: '%')
                    ->required(),

                TextInput::make('ratio_cbd')
                    ->label(__('Ratio CBD'))
                    ->numeric()
                    ->suffix(label: '%')
                    ->required(),

            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->label(__('Name')),

                BadgeColumn::make('seed_type')
                    ->wrap()
                    ->label(__('Seed Type'))
                    ->columnSpan(1)
                    ->alignment('center') // Alineación horizontal
                    ->verticalAlignment('center') // Alineación vertical                        
                    ->colors(colors: [
                        'secondary' => static fn($record): bool => $record->seed_type === 'Fotoperiodica feminizada',
                        'tertiary' => static fn($record): bool => $record->seed_type === 'Fotoperiodica regular',
                        'dark' => static fn($record): bool => $record->seed_type === 'Automatica',
                    ]),

                TextColumn::make('flowering_time')
                    ->searchable()
                    ->label(__('Flowering Time'))
                    ->suffix(' ' . __('weeks'))
                    ->label(__('Flowering Time')),

                TextColumn::make('ratio_thc')
                    ->searchable()
                    ->suffix('%')
                    ->label(__('Ratio THC')),
                TextColumn::make('ratio_cbd')
                    ->searchable()
                    ->suffix('%')
                    ->label(__('Ratio CBD')),
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
            'index' => Pages\ListSeeds::route('/'),
            'create' => Pages\CreateSeed::route('/create'),
            'edit' => Pages\EditSeed::route('/{record}/edit'),
        ];
    }
}
