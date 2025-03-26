<?php

namespace App\Filament\Tenant\Resources\IndoorResource\Pages;

use App\Filament\Tenant\Resources\IndoorResource;
use Filament\Resources\Pages\CreateRecord;

class CreateIndoor extends CreateRecord
{
    protected static string $resource = IndoorResource::class;
    
    protected static ?string $navigationIcon = 'heroicon-o-plus-circle';

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['tenant_id'] = auth()->user()->tenant_id; // Asignar el tenant actual

        return $data;
    }

    protected function getFormActions(): array
    {
        return [
            $this->getCreateFormAction()
                ->icon('heroicon-o-check'),
            $this->getCreateAnotherFormAction()
                ->icon('heroicon-o-plus-circle'),
            $this->getCancelFormAction()
                ->icon('heroicon-o-x-mark'),
        ];
    }
}

