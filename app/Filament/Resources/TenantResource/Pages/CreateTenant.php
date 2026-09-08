<?php

namespace App\Filament\Resources\TenantResource\Pages;

use App\Filament\Resources\TenantResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateTenant extends CreateRecord
{
    protected static string $resource = TenantResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getCreateFormAction(): Actions\Action
    {
        return parent::getCreateFormAction()
            ->requiresConfirmation()
            ->modalHeading(__('Create Tenant'))
            ->modalDescription(function () {
                $data = $this->form->getRawState();
                if (isset($data['active']) && $data['active']) {
                    return __('¿Está seguro de crear este tenant como activo? Se enviará una notificación por email al usuario.');
                }
                return __('¿Está seguro de crear este tenant?');
            });
    }
}
