<?php

namespace App\Filament\Tenant\Resources\ActionsResource\Pages;

use App\Filament\Tenant\Resources\ActionsResource;
use Filament\Actions;
use App\Models\Action;
use App\Models\Plant;
use Filament\Resources\Pages\CreateRecord;
use Filament\Notifications\Notification;
use Filament\Actions\Action as FilamentAction;

class CreateActions extends CreateRecord
{
    protected static string $resource = ActionsResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function handleRecordCreation(array $data): Action
    {
        // Add tenant_id to the data
        $data['tenant_id'] = auth()->user()->tenant_id;

        return Action::create($data);
    }

    protected function afterCreate(): void
    {
        // Execute the action trigger
        $this->executeActionTrigger($this->record);
    }

    protected function executeActionTrigger($action)
    {
        $plants = $action->plants()->get();
        $actionClass = $action->action_type->action_class;

        if (class_exists($actionClass)) {
            static::getResource()::executeActionTrigger($action);
        }
    }

    protected function getCreatedNotification(): ?Notification
    {
        $record = $this->getRecord();

        if ($record->action_type_id == 3) {
            $applicationType = $record->data['product_application']['application_type'] ?? 'desconocido';
            $plantsCount = $record->plants_count;

            return Notification::make()
                ->title('Nueva aplicación de producto')
                ->success()
                ->body("Se ha aplicado un producto de tipo {$applicationType} a {$plantsCount} planta(s)");
        }

        return null;
    }

    protected function getFormActions(): array
    {
        return [
            FilamentAction::make('create')
                ->label(__('Guardar'))
                ->icon('heroicon-o-check')
                ->submit('create'),

            FilamentAction::make('createAnother')
                ->label(__('Guardar y crear otro'))
                ->icon('heroicon-o-plus')
                ->action(function () {
                    $this->create(another: true);
                }),

            FilamentAction::make('cancel')
                ->label(__('Cancelar'))
                ->icon('heroicon-o-arrow-left')
                ->color('gray')
                ->url($this->getResource()::getUrl('index')),
        ];
    }
}
