<?php

namespace App\Filament\Tenant\Resources\Actions\Rules;

class ActionValidationRules
{
    public static function getTransplantRules(): array
    {
        return [
            'new_flowerpot' => ['required', 'string'],
            'new_capacity' => ['required', 'numeric', 'min:1'],
        ];
    }

    public static function getWateringRules(): array
    {
        return [
            'water_amount' => ['required', 'numeric', 'min:0.1'],
            'ph_level' => ['required', 'numeric', 'between:0,14'],
        ];
    }

    // Agregar más métodos para otros tipos de acciones
} 