<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Filament\Notifications\Actions\Action;
use Filament\Notifications\Notification as FilamentNotification;
use Illuminate\Support\Facades\Log;

class ProductApplicationReminder extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        protected string $applicationType,
        protected int $plantsCount
    ) {
        Log::info('ProductApplicationReminder constructor', [
            'applicationType' => $this->applicationType,
            'plantsCount' => $this->plantsCount
        ]);
    }

    public function via($notifiable): array
    {
        Log::info('ProductApplicationReminder via', [
            'notifiableId' => $notifiable->id,
            'notifiableType' => get_class($notifiable)
        ]);
        return ['database'];
    }

    public function toDatabase($notifiable): array
    {
        Log::info('ProductApplicationReminder toDatabase', [
            'notifiableId' => $notifiable->id,
            'applicationType' => $this->applicationType,
            'plantsCount' => $this->plantsCount
        ]);

        return [
            'title' => 'Recordatorio de aplicación pendiente',
            'message' => "Hay que volver a realizar una aplicación de producto",
            'body' => "Hay que volver a realizar una aplicación de producto", // Filament database modal usa data.body
            'format' => 'filament', // requerido para que la campana topbar muestre la notificación
            'icon' => 'heroicon-o-exclamation-triangle',
            'iconColor' => 'warning',
            'actions' => [],
            'application_type' => $this->applicationType,
            'plants_count' => $this->plantsCount,
            'status' => 'success',
            'duration' => 5000,
            'data' => [
                'application_type' => $this->applicationType,
                'plants_count' => $this->plantsCount,
            ],
        ];
    }
} 