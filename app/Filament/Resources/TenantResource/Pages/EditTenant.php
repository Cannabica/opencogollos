<?php

namespace App\Filament\Resources\TenantResource\Pages;

use App\Filament\Resources\TenantResource;
use App\Notifications\TenantActivationNotification;
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

    protected function afterSave(): void
    {
        $tenant = $this->getRecord();
        $originalActive = $tenant->getOriginal('active');
        $newActive = $tenant->active;

        // Check if tenant was just activated (from inactive to active)
        if (!$originalActive && $newActive) {
            Log::debug('Tenant activated from superadmin panel', [
                'tenant_id' => $tenant->id,
                'tenant_name' => $tenant->name
            ]);

            // Get the main user (the one who registered the tenant)
            $mainUser = $tenant->users()->where('email', $tenant->email)->first();

            if ($mainUser) {
                try {
                    $mainUser->notify(new TenantActivationNotification($tenant->name));
                    Log::info('Tenant activation notification sent', [
                        'tenant_id' => $tenant->id,
                        'user_id' => $mainUser->id,
                        'user_email' => $mainUser->email
                    ]);
                } catch (\Exception $e) {
                    Log::error('Failed to send tenant activation notification: ' . $e->getMessage(), [
                        'tenant_id' => $tenant->id,
                        'user_id' => $mainUser->id
                    ]);
                }
            } else {
                Log::warning('Main user not found for tenant activation notification', [
                    'tenant_id' => $tenant->id,
                    'tenant_email' => $tenant->email
                ]);
            }
        }
    }
}
