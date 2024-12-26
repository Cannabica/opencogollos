<?php

namespace App\Filament\Tenant\Resources\IndoorResource\Pages;

use App\Filament\Tenant\Resources\IndoorResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListIndoors extends ListRecords
{
    protected static string $resource = IndoorResource::class;

    public function getSubheading(): ?string
    {
        return __('subheading_list_indoors');
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
