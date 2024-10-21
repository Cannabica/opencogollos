<?php

namespace App\Filament\Tenant\Resources\SeedResource\Pages;

use App\Filament\Tenant\Resources\SeedResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateSeed extends CreateRecord
{
    protected static string $resource = SeedResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Verifica si el usuario está autenticado y tiene un tenant_id
        $data['tenant_id'] = auth()->user()->tenant_id ?? null;
        
        return $data;
    }
}
