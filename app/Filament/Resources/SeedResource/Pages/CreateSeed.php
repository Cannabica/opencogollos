<?php

namespace App\Filament\Resources\SeedResource\Pages;

use App\Filament\Resources\SeedResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateSeed extends CreateRecord
{
    protected static string $resource = SeedResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
