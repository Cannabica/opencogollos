<?php

namespace App\Filament\Resources\SeedResource\Pages;

use App\Filament\Resources\SeedResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListSeeds extends ListRecords
{
    protected static string $resource = SeedResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
