<?php

namespace App\Filament\Tenant\Resources\ActionsResource\Pages;

use App\Filament\Tenant\Resources\ActionsResource;
use Filament\Actions;
use Illuminate\Database\Eloquent\Model;
use App\Models\Action;
use Filament\Resources\Pages\EditRecord;

class EditActions extends EditRecord
{
    protected static string $resource = ActionsResource::class;

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        $record->update($data);

        return $record;
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
