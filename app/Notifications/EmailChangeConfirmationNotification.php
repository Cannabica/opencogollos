<?php

namespace App\Notifications;

use App\Models\EmailChangeRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Mail con el link de confirmación del cambio de email — va a la dirección **NUEVA**.
 *
 * Es la segunda mitad del doble opt-in: mientras este link no se abra, la credencial sigue siendo la
 * dirección vieja. Quien pide el cambio tiene que demostrar que controla la casilla nueva.
 */
class EmailChangeConfirmationNotification extends Notification
{
    use Queueable;

    public function __construct(
        public string $newEmail,
        public string $token,
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

        $subject = 'Confirmá tu nueva dirección de email';
        if (filled($brandName)) {
            $subject .= ' — ' . $brandName;
        }

        return (new MailMessage)
            ->subject($subject)
            ->greeting('Hola ' . ($notifiable->name ?? $this->userName))
            ->line('Pediste usar **' . $this->newEmail . '** para entrar a la plataforma.')
            ->line('Para que el cambio valga, confirmá desde este botón:')
            ->action('Confirmar mi nueva dirección', route('tenant.email.confirm', ['token' => $this->token]))
            ->line('El link vence en ' . EmailChangeRequest::VALID_HOURS . ' horas.')
            ->line('Mientras no lo confirmes, seguís entrando con tu dirección actual: si no pediste este cambio, ignorá este mail y tu cuenta no se modifica.');
    }
}
