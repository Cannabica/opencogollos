<?php

namespace App\Filament\Tenant\Resources\SeedResource\Pages;

use App\Filament\Tenant\Resources\SeedResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListSeeds extends ListRecords
{
    protected static string $resource = SeedResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->icon('heroicon-o-plus'),
        ];
    }

    public function getSubheading(): ?string
    {
        return __('subheading_list_seeds');
    }

    // OJO (T2.6): si alguna vez se quiere volver a "filtros arriba en 3 columnas",
    // en v3 esos ajustes van en el Table del resource: ->filtersFormColumns(3)
    // y ->filtersLayout(FiltersLayout::AboveContent) en SeedResource::table().
    protected function shouldPersistTableFiltersInSession(): bool
    {
        return true;
    }
}
