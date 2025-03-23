<?php

namespace App\Filament\Tenant\Resources\SeedResource\Pages;

use App\Filament\Tenant\Resources\SeedResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Components\Tables\Enums\FiltersLayout;

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

    protected function getTableFiltersFormColumns(): int
    {
        return 3;
    }

    protected function shouldPersistTableFiltersInSession(): bool
    {
        return true;
    }

    protected function getTableFiltersLayout(): ?string 
    {
        return FiltersLayout::AboveContent;
    }
}
