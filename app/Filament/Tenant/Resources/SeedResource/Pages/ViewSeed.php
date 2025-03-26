<?php

namespace App\Filament\Tenant\Resources\SeedResource\Pages;

use App\Filament\Tenant\Resources\SeedResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Notifications\Notification;

class ViewSeed extends ViewRecord
{
    protected static string $resource = SeedResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()
                ->visible(fn ($record) => !$record->isGlobal()),
            Actions\Action::make('copy')
                ->label('Copiar a mi catálogo')
                ->icon('heroicon-o-document-duplicate')
                ->visible(fn ($record) => $record->isGlobal())
                ->action(function ($record) {
                    $newName = str_replace(' - Copia', '', $record->name) . ' - Copia';
                    
                    // Verificar si ya existe una copia con ese nombre
                    $copyNumber = 1;
                    $originalName = $newName;
                    while (\App\Models\Seed::where('name', $newName)
                        ->where('tenant_id', auth()->user()->tenant_id)
                        ->exists()) {
                        $copyNumber++;
                        $newName = $originalName . ' (' . $copyNumber . ')';
                    }

                    $newSeed = $record->replicate();
                    $newSeed->tenant_id = auth()->user()->tenant_id;
                    $newSeed->name = $newName;
                    $newSeed->save();

                    Notification::make()
                        ->success()
                        ->title('Semilla copiada exitosamente')
                        ->body("Se ha creado una copia local de la semilla '{$record->name}' con el nombre '{$newName}'.")
                        ->send();

                    return redirect()->to(SeedResource::getUrl('index'));
                }),
        ];
    }
} 