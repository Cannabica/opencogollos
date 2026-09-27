<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Invitación a un usuario nuevo del grupo: **sin clave**, la define él en su primer ingreso.
 *
 * Es la otra modalidad del alta (ver TenantPage::addUser). En vez de mandarle una contraseña temporal
 * por mail —que hay que copiar y después cambiar igual— se le manda un link para establecer la suya.
 * El link es el mismo flujo de recuperación (token del broker `users`, 48 h) y el campo donde escribe
 * la contraseña tiene la validación visual en vivo.
 */
class TeamUserInvitationNotification extends Notification
{
    use Queueable;

    public function __construct(
        public string $tenantName,
        public string $userName,
        public string $invitationUrl,
    ) {
    }

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $brandName = config('platform.brand_name');

        $subject = 'Te sumaron a ' . $this->tenantName;
        if (filled($brandName)) {
            $subject .= ' — ' . $brandName;
        }

        return (new MailMessage)
            ->subject($subject)
            ->greeting('¡Hola ' . $this->userName . '!')
            ->line('Te agregaron al grupo **' . $this->tenantName . '**.')
            ->line('Para entrar, elegí tu contraseña desde el botón de abajo. No te mandamos ninguna clave: la definís vos.')
            ->action('Elegir mi contraseña', $this->invitationUrl)
            ->line('El link vence en 48 horas. Si se vence, pedí uno nuevo desde "olvidé mi contraseña" en la pantalla de ingreso.')
            ->line('Mientras no la definas, la cuenta no tiene acceso.');
    }
}
