<?php

namespace App\Filament\Tenant\Resources\IndoorResource\Pages;

use App\Filament\Tenant\Resources\IndoorResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditIndoor extends EditRecord
{
    protected static string $resource = IndoorResource::class;
    
    protected static ?string $navigationIcon = 'heroicon-o-pencil-square';

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make()
                ->icon('heroicon-o-eye'),
            Actions\DeleteAction::make()
                ->icon('heroicon-o-trash'),
        ];
    }

    protected function getFormActions(): array
    {
        return [
            $this->getSaveFormAction()
                ->icon('heroicon-o-check'),
            $this->getCancelFormAction()
                ->icon('heroicon-o-x-mark'),
        ];
    }
}
