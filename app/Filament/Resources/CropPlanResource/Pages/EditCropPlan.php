<?php

namespace App\Filament\Resources\CropPlanResource\Pages;

use App\Filament\Resources\CropPlanResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCropPlan extends EditRecord
{
    protected static string $resource = CropPlanResource::class;

    public function getSubheading(): ?string
    {
    return __('subheading_cropplan');
    }
    
    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
