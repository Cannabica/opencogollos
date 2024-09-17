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
                    ->required(),
                ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('attention_type_id')
            ->columns([
                Tables\Columns\TextColumn::make('attention_type.name')
                    ->label(__('Attention Type')),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
