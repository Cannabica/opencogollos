<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TenantResource\Pages;
use App\Filament\Resources\TenantResource\RelationManagers;
use App\Models\Tenant;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Tables\Columns\CheckboxColumn;
use App\Filament\Resources\TenantResource\RelationManagers\UsersRelationManager;

class TenantResource extends Resource
{
    protected static ?string $model = Tenant::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';

    public static function getPluralLabel(): string
    {
        return __('Tenants');
    }
    public static function getLabel(): string
    {
        return __('Tenant');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->label(__('Name'))
                    ->required(),
                Forms\Components\TextInput::make('email')
                    ->label(__('Email'))
                    ->email()
                    ->required(),
                Forms\Components\Checkbox::make('active')
                    ->label(__('Active')),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                ->label(__('Name')),
                Tables\Columns\TextColumn::make('email'),
                CheckboxColumn::make('active')
                ->label(__('Active')),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->requiresConfirmation()
                    ->modalHeading(function ($record) {
                        return __('Edit Tenant') . ' - ' . $record->name;
                    })
                    ->modalDescription(__('¿Está seguro de que desea editar este tenant? Si cambia el estado de activación, se enviará una notificación por email al usuario.')),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('activate')
                        ->label(__('Activate selected'))
                        ->action(function ($records) {
                            $records->each(function ($record) {
                                $record->update(['active' => true]);
                            });
                        })
                        ->requiresConfirmation()
                        ->modalHeading(__('Activate Tenants'))
                        ->modalDescription(__('¿Está seguro de que desea activar los tenants seleccionados? Se enviarán notificaciones por email a los usuarios afectados.'))
                        ->deselectRecordsAfterCompletion(),
                    Tables\Actions\BulkAction::make('deactivate')
                        ->label(__('Deactivate selected'))
                        ->action(function ($records) {
                            $records->each(function ($record) {
                                $record->update(['active' => false]);
                            });
                        })
                        ->requiresConfirmation()
                        ->modalHeading(__('Deactivate Tenants'))
                        ->modalDescription(__('¿Está seguro de que desea desactivar los tenants seleccionados? Se enviarán notificaciones por email a los usuarios afectados.'))
                        ->deselectRecordsAfterCompletion(),
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            UsersRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTenants::route('/'),
            'create' => Pages\CreateTenant::route('/create'),
            'edit' => Pages\EditTenant::route('/{record}/edit'),
        ];
    }
}
