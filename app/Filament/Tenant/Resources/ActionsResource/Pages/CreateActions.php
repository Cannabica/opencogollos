<?php

namespace App\Filament\Tenant\Resources\ActionsResource\Pages;

use App\Filament\Tenant\Resources\ActionsResource;
use Filament\Actions;
use App\Models\Action;
use Filament\Resources\Pages\CreateRecord;

class CreateActions extends CreateRecord
{
    protected static string $resource = ActionsResource::class;

    protected function handleRecordCreation(array $data): Action
    {
        return Action::create($data); 
    }
}
