<?php

namespace App\Filament\Tenant\Resources;

use App\Filament\Tenant\Resources\CropPlanResource\Pages;
use App\Filament\Tenant\Resources\CropPlanResource\RelationManagers;
use App\Models\CropPlan;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class CropPlanResource extends Resource
{
    protected static ?string $model = CropPlan::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                //
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                //
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
            'index' => Pages\ListCropPlans::route('/'),
            'create' => Pages\CreateCropPlan::route('/create'),
            'edit' => Pages\EditCropPlan::route('/{record}/edit'),
        ];
    }
}
