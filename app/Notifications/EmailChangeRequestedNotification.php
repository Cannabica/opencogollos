<?php

namespace App\Notifications;

use App\Models\EmailChangeRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Aviso a la dirección **VIEJA** de que alguien pidió cambiar el email de la cuenta.
 *
 * Este es el mail que hace visible el abuso: si el pedido no fue tuyo, te enterás en la casilla que
 * todavía controlás (la nueva no la controlás). Va solo a la dirección vieja, así que no hay riesgo de
 * que el token caiga donde no debe: acá no viaja ningún link de confirmación.
 */
class EmailChangeRequestedNotification extends Notification
{
    use Queueable;

    public function __construct(
        public string $oldEmail,
        public string $newEmail,
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

        $subject = 'Pidieron cambiar el email de tu cuenta';
        if (filled($brandName)) {
            $subject .= ' — ' . $brandName;
        }

        $message = (new MailMessage)
            ->subject($subject)
            ->greeting('Hola ' . ($notifiable->name ?? $this->userName))
            ->line('Se pidió cambiar el email de acceso de tu cuenta el ' . now()->format('d/m/Y') . ' a las ' . now()->format('H:i') . '.')
            ->line('**Dirección actual:** ' . $this->oldEmail)
            ->line('**Dirección pedida:** ' . $this->newEmail)
            ->line(' ')
            ->line('El cambio **todavía no está hecho**: hay que confirmarlo abriendo un link que mandamos a la dirección nueva, y el link vence en ' . EmailChangeRequest::VALID_HOURS . ' horas.')
            ->line('Mientras tanto seguís entrando con tu dirección actual.')
            ->line(' ')
            ->line('⚠️ **Si no pediste este cambio**, alguien más tiene acceso a tu cuenta: cambiá tu contraseña ahora y escribinos.')
            ->action('Ir a mi cuenta', url('/tenant/login'));

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
