<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TenantDeactivationNotification extends Notification // implements ShouldQueue
{
    use Queueable;

    public $tenantName;

    /**
     * Create a new notification instance.
     */
    public function __construct(string $tenantName)
    {
        $this->tenantName = $tenantName;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Desactivación de Tenant - ' . $this->tenantName)
            ->greeting('Hola ' . $notifiable->name . ',')
            ->line('Tu tenant ' . $this->tenantName . ' ha sido desactivado.')
            ->line('')
            ->line('Si crees que esto es un error o necesitas reactivar tu cuenta, por favor contacta con el administrador del sistema.')
            ->line('')
            ->salutation('Para consultas o soporte, contacta con el administrador del sistema.');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'tenant_name' => $this->tenantName,
            'deactivation_date' => now(),
        ];
    }
}