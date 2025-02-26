<?php

namespace App\Filament\Tenant\Resources\ActionsResource\Pages;

use App\Filament\Tenant\Resources\ActionsResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListActions extends ListRecords
{
    protected static string $resource = ActionsResource::class;
    
    public function getSubheading(): ?string
    {
        return __('actions_Subheading');
    }

    protected function getHeaderActions(): array
    {
        return [
            // Actions\CreateAction::make(),
            Actions\CreateAction::make()
            ->icon('heroicon-o-plus') 
        ];
    }
}
