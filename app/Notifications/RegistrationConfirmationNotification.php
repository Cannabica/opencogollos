<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class RegistrationConfirmationNotification extends Notification implements ShouldQueue
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
        // La invitación a Discord solo se menciona si hay una comunidad configurada.
        $status = 'Tu cuenta está siendo verificada. Te notificaremos por correo electrónico cuando esté lista';
        $status .= filled(config('platform.community.discord_url'))
            ? ', sumate al servidor de discord si querés interactuar o realizar alguna consulta.'
            : '.';

        return (new MailMessage)
            ->subject('Confirmación de Registro - ' . $this->tenantName)
            ->greeting('¡Hola ' . $notifiable->name . '!')
            ->line('Tu registro en nuestra plataforma ha sido exitoso.')
            ->line('**Detalles de tu cuenta:**')
            ->line('Nombre: ' . $notifiable->name)
            ->line('Email: ' . $notifiable->email)
            ->line('Tenant: ' . $this->tenantName)
            ->line('')
            ->line($status)
            ->action('Iniciar Sesión', url('/tenant/login'))
            ->line('')
            ->salutation('Gracias por unirte a nuestra comunidad!');
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
        ];
    }
}