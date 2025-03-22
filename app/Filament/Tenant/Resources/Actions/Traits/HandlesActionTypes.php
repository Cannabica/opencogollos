<?php

namespace App\Filament\Tenant\Resources\Actions\Traits;

use App\Filament\Tenant\Resources\Actions\Rules\ActionValidationRules;

trait HandlesActionTypes
{
    protected function getActionTypeFields(int $actionTypeId): array
    {
        return match($actionTypeId) {
            1 => $this->getWateringFields(),
            4 => $this->getTransplantFields(),
            // Agregar más tipos según sea necesario
            default => [],
        };
    }

    protected function getActionTypeRules(int $actionTypeId): array
    {
        return match($actionTypeId) {
            1 => ActionValidationRules::getWateringRules(),
            4 => ActionValidationRules::getTransplantRules(),
            default => [],
        };
    }

    private function getTransplantFields(): array
    {
        // Definir campos específicos para transplante
    }

    private function getWateringFields(): array
    {
        // Definir campos específicos para riego
    }
} 