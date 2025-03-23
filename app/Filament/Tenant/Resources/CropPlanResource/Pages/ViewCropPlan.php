<?php

namespace App\Filament\Tenant\Resources\CropPlanResource\Pages;

use App\Filament\Tenant\Resources\CropPlanResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Notifications\Notification;

class ViewCropPlan extends ViewRecord
{
    protected static string $resource = CropPlanResource::class;

    public function getSubheading(): ?string
    {
        return __('subheading_cropplan');
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()
                ->visible(fn ($record) => !$record->isGlobal()),
            Actions\Action::make('duplicate')
                ->label('Copiar a mi catálogo')
                ->icon('heroicon-o-document-duplicate')
                ->visible(fn ($record) => $record->isGlobal())
                ->action(function ($record) {
                    $newName = str_replace(' - Copia', '', $record->name) . ' - Copia';
                    
                    // Verificar si ya existe una copia con ese nombre
                    $copyNumber = 1;
                    $originalName = $newName;
                    while (\App\Models\CropPlan::where('name', $newName)
                        ->where('tenant_id', auth()->user()->tenant_id)
                        ->exists()) {
                        $copyNumber++;
                        $newName = $originalName . ' (' . $copyNumber . ')';
                    }

                    $newPlan = $record->replicate();
                    $newPlan->tenant_id = auth()->user()->tenant_id;
                    $newPlan->name = $newName;
                    $newPlan->save();

                    Notification::make()
                        ->success()
                        ->title('Plan de cultivo copiado exitosamente')
                        ->body("Se ha creado una copia local del plan '{$record->name}' con el nombre '{$newName}'.")
                        ->send();
                }),
        ];
    }
} 