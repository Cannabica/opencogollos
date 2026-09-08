<?php

namespace App\Filament\Tenant\Resources\NotificationResource\Pages;

use App\Filament\Tenant\Resources\NotificationResource;
use Filament\Resources\Pages\ListRecords;

class ListNotifications extends ListRecords
{
    protected static string $resource = NotificationResource::class;

    protected function getFooterWidgets(): array
    {
        return [
            \App\Filament\Tenant\Widgets\UpcomingNotificationsWidget::class,
        ];
    }
}
