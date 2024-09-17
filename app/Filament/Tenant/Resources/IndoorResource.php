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
use Filament\Tables\Columns\TextColumn;

class IndoorResource extends Resource
{
    protected static ?string $model = Indoor::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('name')
                    ->label(__('Name'))
                    ->required(),
                Forms\Components\Hidden::make('tenant_id')
                    ->default(fn () => auth()->user()->tenant_id),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name'),
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
            'index' => Pages\ListIndoors::route('/'),
            'create' => Pages\CreateIndoor::route('/create'),
            'edit' => Pages\EditIndoor::route('/{record}/edit'),
        ];
    }
}
