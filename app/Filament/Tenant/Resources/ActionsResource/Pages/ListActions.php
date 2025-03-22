<?php

namespace App\Filament\Tenant\Resources\ActionsResource\Pages;

use App\Filament\Tenant\Resources\ActionsResource;
use App\Models\Action as ActionModel;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListActions extends ListRecords
{
    protected static string $resource = ActionsResource::class;
    
    public function getSubheading(): ?string
    {
        return __('actions_Subheading');
    }

    protected function getHeaderActions(): array
    {
        return [
            // Actions\CreateAction::make(),
            Actions\CreateAction::make()
                ->icon('heroicon-o-plus'),
            
            Actions\Action::make('repeatLastIrrigation')
                ->label(__('Repetir último riego'))
                ->icon(new \Illuminate\Support\HtmlString('
                    <svg class="w-6 h-6" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M12 21C15.866 21 19 17.866 19 14C19 10.5067 15.9333 6.71333 13.4667 4.26667C12.6667 3.46667 11.3333 3.46667 10.5333 4.26667C8.06667 6.71333 5 10.5067 5 14C5 17.866 8.13401 21 12 21Z" 
                              stroke="currentColor" 
                              stroke-width="2" 
                              stroke-linecap="round" 
                              stroke-linejoin="round"
                              fill="currentColor"
                              fill-opacity="0.2"/>
                    </svg>
                '))
                ->visible(fn () => ActionModel::where('tenant_id', auth()->user()->tenant_id)
                    ->where('action_type_id', 1)
                    ->exists())
                ->action(function () {
                    $lastIrrigation = ActionModel::where('tenant_id', auth()->user()->tenant_id)
                        ->where('action_type_id', 1)
                        ->latest()
                        ->first();

                    if (!$lastIrrigation) {
                        return;
                    }

                    // Convertimos el array de IDs de plantas a una cadena JSON
                    $plantIds = $lastIrrigation->plants->pluck('id')->toArray();

                    return redirect()->to(ActionsResource::getUrl('create', [
                        'indoor_id' => $lastIrrigation->indoor_id,
                        'action_type_id' => 1,
                        'irrigation_type' => $lastIrrigation->data['irrigation']['irrigation_type'] ?? '',
                        'irrigation_value' => $lastIrrigation->data['irrigation']['irrigation_type'] === 'liters' 
                            ? ($lastIrrigation->data['irrigation']['liters'] ?? '')
                            : ($lastIrrigation->data['irrigation']['timer'] ?? ''),
                        'selected_plants' => json_encode($plantIds) // Usamos un nombre diferente y JSON
                    ]));
                })
        ];
    }
}
