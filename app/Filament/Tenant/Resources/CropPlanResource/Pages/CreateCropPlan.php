<?php

namespace App\Filament\Tenant\Resources\CropPlanResource\Pages;

use App\Filament\Tenant\Resources\CropPlanResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateCropPlan extends CreateRecord
{
    protected static string $resource = CropPlanResource::class;

    public function getSubheading(): ?string
    {
        return __('subheading_cropplan');
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
