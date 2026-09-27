<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Aviso de cambio de contraseña (mail a la cuenta).
 *
 * Por qué existe: cuando alguien cambia la contraseña, el dueño de la cuenta tiene que enterarse
 * — es la única forma de que un cambio NO autorizado se note. Por eso el mail no es solo un
 * "listo": lleva el **método de recuperación** (restablecer contraseña) y, si la instalación tiene
 * contacto configurado, **con quién hablar**.
 *
 * Marca blanca: los datos de contacto salen de `config('platform.*')` (mismas claves que usa el
 * resto del producto). Si una instalación no configuró nada, el mail sale igual —sin el bloque de
 * contacto— porque el aviso y el link de recuperación son el mínimo que no se puede omitir.
 */
class PasswordChangedNotification extends Notification
{
    use Queueable;

    /** El usuario lo hizo desde "Cambiar contraseña". */
    public const CONTEXT_VOLUNTARY = 'voluntary';

    /** El sistema lo obligó (primer acceso / reset administrativo). */
    public const CONTEXT_FORCED = 'forced';

    public function __construct(public string $context = self::CONTEXT_VOLUNTARY)
    {
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

        $subject = 'Tu contraseña cambió';
        if (filled($brandName)) {
            $subject .= ' — ' . $brandName;
        }

        $message = (new MailMessage)
            ->subject($subject)
            ->greeting('Hola ' . $notifiable->name)
            ->line('La contraseña de tu cuenta cambió el ' . now()->format('d/m/Y') . ' a las ' . now()->format('H:i') . '.');

        $message->line($this->context === self::CONTEXT_FORCED
            ? 'Fue un cambio obligatorio: la plataforma te pidió renovarla al ingresar.'
            : 'El cambio lo hiciste desde tu cuenta.');

        $message->line('Si fuiste vos, no tenés que hacer nada más: entrá con tu contraseña nueva.');

        // Método de recuperación: el camino para volver a entrar si el cambio NO fue tuyo.
        $message->line('**Si NO reconocés este cambio**, restablecé tu contraseña ahora mismo:');
        $message->action('Restablecer mi contraseña', url('/tenant/password-reset/request'));

        // Contacto del mantenedor de la instalación (si está configurado).
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
