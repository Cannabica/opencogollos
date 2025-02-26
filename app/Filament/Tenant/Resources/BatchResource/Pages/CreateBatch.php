<?php

namespace App\Filament\Tenant\Resources\BatchResource\Pages;

use App\Filament\Tenant\Resources\BatchResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateBatch extends CreateRecord
{
    protected static string $resource = BatchResource::class;
}
