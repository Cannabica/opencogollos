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
            foreach($plants as $plant) {
                // Obtener los argumentos de forma estática
                $constructorArgs = $actionClass::getConstructorArguments($action);

                // Crear la instancia real con los argumentos del constructor
                $actionInstance = new $actionClass();

                // Llamar al método trigger con la planta como argumento
                $actionInstance->trigger($plant, $constructorArgs);
            }
        }
    }

    protected function getCreatedNotification(): ?Notification
    {
        $record = $this->getRecord();
        
        if ($record->action_type_id == 3) {
            $applicationType = $record->data['product_application']['application_type'] ?? 'desconocido';
            $plantsCount = $record->plants_count;
            $reminderTime = $record->data['product_application']['reminder_time'] ?? 'none';
            
            if ($reminderTime !== 'none') {
                if ($reminderTime === '5s') {
                    // Disparar inmediatamente sin delay para testing cuando APP_DEBUG=true
                    if (env('APP_DEBUG') === true) {
                        // Disparar con delay aleatorio entre 5 y 10 segundos para testing
                        $randomDelay = rand(5, 10);
                        \App\Jobs\SendDelayedProductNotification::dispatch(
                            $applicationType,
                            $plantsCount,
                            auth()->user()->tenant_id,
                            $record->id
                        )->delay(now()->addSeconds($randomDelay));
                    } else {
                        // En producción, usar el delay normal de 5 segundos
                        \App\Jobs\SendDelayedProductNotification::dispatch(
                            $applicationType,
                            $plantsCount,
                            auth()->user()->tenant_id,
                            $record->id
                        )->delay(now()->addSeconds(5));
                    }
                } else {
                    $delay = match($reminderTime) {
                        '1m' => now()->addMinute(),
                        '1d' => now()->addDay(),
                        '5s' => now()->addSeconds(5),
                        default => null
                    };

                    if ($delay) {
                        \App\Jobs\SendDelayedProductNotification::dispatch(
                            $applicationType,
                            $plantsCount,
                            auth()->user()->tenant_id,
                            $record->id
                        )->delay($delay);
                    }
                }
            }
            
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
