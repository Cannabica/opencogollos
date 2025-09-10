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
    public $tenantName;

    /**
     * Create a new notification instance.
     */
    public function __construct(string $password, string $tenantName)
    {
        $this->password = $password;
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
            ->subject('Activación de cuenta - ' . $this->tenantName)
            ->greeting('¡Hola ' . $notifiable->name . '!')
            ->line('Has sido agregado al equipo de ' . $this->tenantName)
            ->line('**Credenciales temporales:**')
            ->line('Email: ' . $notifiable->email)
            ->line('Contraseña temporal: ' . $this->password)
            ->line('')
            ->line('**Importante:** Deberás cambiar tu contraseña en el primer inicio de sesión.')
            ->action('Iniciar Sesión', url('/tenant/login'))
            ->line('Tu cuenta estará activa una vez que el administrador la active.');
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
            'tenant_name' => $this->tenantName,
        ];
    }
}