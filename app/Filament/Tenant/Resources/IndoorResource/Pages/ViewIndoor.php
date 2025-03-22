<?php

namespace App\Filament\Tenant\Resources\IndoorResource\Pages;

use App\Filament\Tenant\Resources\IndoorResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Resources\Table;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\DeleteAction;

class ViewIndoor extends ViewRecord
{
    protected static string $resource = IndoorResource::class;

    protected static ?string $navigationIcon = 'heroicon-o-eye';

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()
                ->icon('heroicon-o-pencil'),
            Actions\DeleteAction::make()
                ->icon('heroicon-o-trash'),
        ];
    }

    protected function getTable(): Table
    {
        // Implementation of getTable method
    }
} 