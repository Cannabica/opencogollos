<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Aviso de cambio de email de la cuenta — **a la dirección vieja y a la nueva**.
 *
 * Por qué va a las dos: el email es la credencial de login. Si alguien entra con una sesión ajena y
 * cambia el email, la víctima se enteraría *sólo* por la casilla nueva… que es justo la que el
 * atacante controla. Avisar a la dirección **vieja** es lo que hace visible el cambio no autorizado.
 *
 * Por eso esta notificación se envía a mano a las dos direcciones (`Notification::route('mail', ...)`)
 * en vez de dejar que el `Notifiable` resuelva una sola.
 *
 * Mismo criterio de marca blanca que el resto: el contacto del servicio sale de `config('platform.*')`
 * y se omite si la instalación no lo configuró.
 */
class EmailChangedNotification extends Notification
{
    use Queueable;

    /** El cambio lo hizo la persona desde su cuenta. */
    public const DESTINATION_OLD = 'old';

    /** El cambio lo hizo la persona desde su cuenta. */
    public const DESTINATION_NEW = 'new';

    public function __construct(
        public string $oldEmail,
        public string $newEmail,
        public string $destination,
        public string $userName,
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
        $adminEmail = config('platform.admin_email');
        $siteUrl = config('platform.site_url') ?: config('platform.platform_url');

        $subject = 'Tu email de acceso cambió';
        if (filled($brandName)) {
            $subject .= ' — ' . $brandName;
        }

        $message = (new MailMessage)
            ->subject($subject)
            ->greeting('Hola ' . ($notifiable->name ?? $this->userName))
            ->line('El email con el que entrás a la plataforma cambió el ' . now()->format('d/m/Y') . ' a las ' . now()->format('H:i') . '.')
            ->line('**Dirección anterior:** ' . $this->oldEmail)
            ->line('**Dirección nueva:** ' . $this->newEmail);

        if ($this->destination === self::DESTINATION_OLD) {
            // A la dirección vieja hay que decirle algo más: si el cambio no fue suyo, tiene que actuar
            // YA y sabe que la casilla nueva no le pertenece.
            $message->line(' ');
            $message->line('⚠️ **Este aviso llega a la dirección anterior.** Si NO hiciste este cambio, alguien más tiene acceso a tu cuenta: escribinos y restablecé el acceso desde el link de abajo.');
        } else {
            $message->line('Si fuiste vos, ya podés entrar con la dirección nueva.');
        }

        $message->line(' ');
        $message->line('Para volver a entrar o restablecer el acceso:');
        $message->action('Ir a la plataforma', url('/tenant/login'));

        if (filled($adminEmail) || filled($siteUrl)) {
            $message->line(' ');
            $message->line('**Contacto del servicio**');

            if (filled($brandName)) {
                $message->line('Servicio: ' . $brandName);
            }

            if (filled($adminEmail)) {
                $message->line('Email: ' . $adminEmail);
            }

            if (filled($siteUrl)) {
                $message->line('Sitio: ' . $siteUrl);
            }
        }

        return $message;
    }
}
