<?php

namespace App\Filament\Resources\TenantResource\Pages;

use App\Filament\Resources\TenantResource;
use App\Notifications\TenantActivationNotification;
use App\Notifications\TenantDeactivationNotification;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Log;

class EditTenant extends EditRecord
{
    protected static string $resource = TenantResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function getSaveFormAction(): Actions\Action
    {
        return parent::getSaveFormAction()
            ->requiresConfirmation()
            ->modalHeading(__('Save Changes'))
            ->modalDescription(function () {
                $data = $this->form->getRawState();
                $originalActive = $this->getRecord()->getOriginal('active');
                $newActive = isset($data['active']) ? (bool)$data['active'] : false;

                // Check if tenant is being activated
                if (!$originalActive && $newActive) {
                    return __('¿Está seguro de activar este tenant? Se enviará una notificación por email al usuario.');
                }

                // Check if tenant is being deactivated
                if ($originalActive && !$newActive) {
                    return __('¿Está seguro de desactivar este tenant? Se enviará una notificación por email al usuario.');
                }

                return __('¿Está seguro de guardar los cambios?');
            });
    }

    protected function afterSave(): void
    {
        $tenant = $this->getRecord();
        $originalActive = $tenant->getOriginal('active');
        $newActive = $tenant->active;

        Log::debug('Tenant afterSave debug', [
            'tenant_id' => $tenant->id,
            'tenant_name' => $tenant->name,
            'original_active' => $originalActive,
            'new_active' => $newActive,
            'tenant_email' => $tenant->email
        ]);

        // Check if tenant was just activated (from inactive to active)
        if (!$originalActive && $newActive) {
            Log::debug('Tenant activated from superadmin panel', [
                'tenant_id' => $tenant->id,
                'tenant_name' => $tenant->name
            ]);

            // Get the main user (the one who registered the tenant)
            $mainUser = $tenant->users()->where('email', $tenant->email)->first();

            Log::debug('Main user search for activation', [
                'tenant_id' => $tenant->id,
                'tenant_email' => $tenant->email,
                'main_user_found' => !is_null($mainUser),
                'main_user_id' => $mainUser?->id,
                'main_user_email' => $mainUser?->email
            ]);

            if ($mainUser) {
                try {
                    Log::debug('Attempting to send tenant activation notification', [
                        'tenant_id' => $tenant->id,
                        'user_id' => $mainUser->id,
                        'user_email' => $mainUser->email
                    ]);
                    
                    $mainUser->notify(new TenantActivationNotification($tenant->name));
                    Log::info('Tenant activation notification sent', [
                        'tenant_id' => $tenant->id,
                        'user_id' => $mainUser->id,
                        'user_email' => $mainUser->email
                    ]);
                } catch (\Exception $e) {
                    Log::error('Failed to send tenant activation notification: ' . $e->getMessage(), [
                        'tenant_id' => $tenant->id,
                        'user_id' => $mainUser->id,
                        'exception' => $e->getTraceAsString()
                    ]);
                }
            } else {
                Log::warning('Main user not found for tenant activation notification', [
                    'tenant_id' => $tenant->id,
                    'tenant_email' => $tenant->email,
                    'available_users' => $tenant->users()->pluck('email')->toArray()
                ]);
            }
        }

        // Check if tenant was just deactivated (from active to inactive)
        if ($originalActive && !$newActive) {
            Log::debug('Tenant deactivated from superadmin panel', [
                'tenant_id' => $tenant->id,
                'tenant_name' => $tenant->name
            ]);

            // Get the main user (the one who registered the tenant)
            $mainUser = $tenant->users()->where('email', $tenant->email)->first();

            Log::debug('Main user search for deactivation', [
                'tenant_id' => $tenant->id,
                'tenant_email' => $tenant->email,
                'main_user_found' => !is_null($mainUser),
                'main_user_id' => $mainUser?->id,
                'main_user_email' => $mainUser?->email
            ]);

            if ($mainUser) {
                try {
                    Log::debug('Attempting to send tenant deactivation notification', [
                        'tenant_id' => $tenant->id,
                        'user_id' => $mainUser->id,
                        'user_email' => $mainUser->email
                    ]);
                    
                    $mainUser->notify(new TenantDeactivationNotification($tenant->name));
                    Log::info('Tenant deactivation notification sent', [
                        'tenant_id' => $tenant->id,
                        'user_id' => $mainUser->id,
                        'user_email' => $mainUser->email
                    ]);
                } catch (\Exception $e) {
                    Log::error('Failed to send tenant deactivation notification: ' . $e->getMessage(), [
                        'tenant_id' => $tenant->id,
                        'user_id' => $mainUser->id,
                        'exception' => $e->getTraceAsString()
                    ]);
                }
            } else {
                Log::warning('Main user not found for tenant deactivation notification', [
                    'tenant_id' => $tenant->id,
                    'tenant_email' => $tenant->email,
                    'available_users' => $tenant->users()->pluck('email')->toArray()
                ]);
            }
        }
    }
}
