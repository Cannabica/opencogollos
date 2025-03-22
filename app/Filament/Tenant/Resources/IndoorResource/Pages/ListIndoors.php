<?php

namespace App\Filament\Tenant\Resources\IndoorResource\Pages;

use App\Filament\Tenant\Resources\IndoorResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListIndoors extends ListRecords
{
    protected static string $resource = IndoorResource::class;
    
    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public function getSubheading(): ?string
    {
        return __('subheading_list_indoors');
    }

    protected function getFooterActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->icon('heroicon-o-plus')
                ->label(__('Add Indoor')),
        ];
    }
}
