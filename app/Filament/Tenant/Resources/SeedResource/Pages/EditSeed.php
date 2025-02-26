<?php

namespace App\Filament\Tenant\Resources\SeedResource\Pages;

use App\Filament\Tenant\Resources\SeedResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditSeed extends EditRecord
{
    protected static string $resource = SeedResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
