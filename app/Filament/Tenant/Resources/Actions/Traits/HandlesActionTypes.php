<?php

namespace App\Filament\Tenant\Resources\Actions\Traits;

use App\Filament\Tenant\Resources\Actions\Rules\ActionValidationRules;

trait HandlesActionTypes
{
    protected function getActionTypeRules(int $actionTypeId): array
    {
        return match($actionTypeId) {
            1 => ActionValidationRules::getWateringRules(),
            4 => ActionValidationRules::getTransplantRules(),
            default => [],
        };
    }
} 