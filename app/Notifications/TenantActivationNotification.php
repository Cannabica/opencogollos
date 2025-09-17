<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TenantActivationNotification extends Notification implements ShouldQueue
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
            ->subject('Activación de Tenant - ' . $this->tenantName)
            ->greeting('¡Hola ' . $notifiable->name . '!')
            ->line('Tu tenant ' . $this->tenantName . ' ha sido activado exitosamente.')
            ->line('**Detalles de tu cuenta:**')
            ->line('Nombre: ' . $notifiable->name)
            ->line('Email: ' . $notifiable->email)
            ->line('Tenant: ' . $this->tenantName)
            ->line('')
            ->line('Ahora puedes acceder a todas las funcionalidades de la plataforma.')
            ->line('Si necesitas restablecer tu contraseña, puedes hacerlo desde la página de login.')
            ->action('Iniciar Sesión', url('/tenant/login'))
            ->line('¡Bienvenido a nuestra comunidad!')
            ->line('')
            ->line('Para ver el estado del sistema, visita: https://status.cannabica.ar');
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
            'activation_date' => now(),
        ];
    }
}