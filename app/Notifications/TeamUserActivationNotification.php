<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TeamUserActivationNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public $password;

    /**
     * Create a new notification instance.
     */
    public function __construct(string $password)
    {
        $this->password = $password;
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
        $tenantName = $notifiable->tenant ? $notifiable->tenant->name : 'Sistema';
        
        return (new MailMessage)
            ->subject('Activación de cuenta - ' . $tenantName)
            ->greeting('¡Hola ' . $notifiable->name . '!')
            ->line('Has sido agregado al equipo de ' . $tenantName)
            ->line('**Credenciales temporales:**')
            ->line('Email: ' . $notifiable->email)
            ->line('Contraseña temporal: ' . $this->password)
            ->line('')
            ->line('**Importante:** Deberás cambiar tu contraseña en el primer inicio de sesión.')
            ->action('Iniciar Sesión', url('/tenant/login'))
            ->line('Tu cuenta está activa y lista para usar.')
            ->line('')
            ->line('Para ver el estado del sistema, visita: https://status.cannabica.ar')
            ->salutation('Si tienes alguna duda, no dudes en contactarnos.');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'password' => $this->password,
            'tenant_name' => $notifiable->tenant ? $notifiable->tenant->name : 'Sistema',
        ];
    }
}