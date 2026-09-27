<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Log;

/**
 * Evento de seguridad (trazabilidad separada de la telemetría de uso).
 *
 * `usage_events` mide cómo se usa el producto (pageviews); esto es otra cosa: acciones con
 * consecuencia sobre la cuenta y la seguridad. Hoy hay un solo tipo —cambio de contraseña— y el
 * modelo queda preparado para los que vengan (cambio de email, baja de cuenta, login fallido).
 */
class SecurityEvent extends Model
{
    /**
     * Solo created_at: los eventos de seguridad son inmutables por diseño.
     */
    public const UPDATED_AT = null;

    /** Evento: el usuario cambio su contrasena (ver `context` para el motivo). */
    public const PASSWORD_CHANGED = 'password_changed';

    /**
     * Evento: cambió el email de la cuenta.
     *
     * El email es la credencial de login, así que este cambio es de ALTO RIESGO: se exige la
     * contraseña actual y se avisa a la dirección vieja Y a la nueva (el aviso a la vieja es lo que
     * hace visible un cambio no autorizado: el atacante no controla esa casilla).
     */
    public const EMAIL_CHANGED = 'email_changed';

    /**
     * Evento: cambió el email de contacto del GRUPO (no es credencial de login, pero es el canal de
     * contacto del grupo: lo cambia sólo el owner y queda registrado).
     */
    public const TENANT_EMAIL_CHANGED = 'tenant_email_changed';

    /**
     * Evento: se sumó una persona al grupo. El `context` guarda la modalidad del alta
     * (`password` = se le mandó una clave segura; `self` = define la suya en el primer ingreso).
     */
    public const TEAM_USER_INVITED = 'team_user_invited';

    /**
     * Evento: cambió quién administra el grupo. El `context` distingue si fue la designación inicial
     * (`assigned`) o una transferencia a otra persona (`transferred`).
     */
    public const OWNER_CHANGED = 'owner_changed';

    /** El usuario eligió cambiarla desde "Cambiar contraseña". */
    public const CONTEXT_VOLUNTARY = 'voluntary';

    /** El sistema la obligó (primer acceso / reset administrativo). */
    public const CONTEXT_FORCED = 'forced';

    /** La persona la definió al entrar por una invitación (primer acceso auto-gestionado). */
    public const CONTEXT_INITIAL = 'initial';

    protected $fillable = [
        'tenant_id',
        'user_id',
        'event',
        'context',
    ];

    /**
     * Registra un evento de seguridad.
     *
     * Nunca tira excepción: igual que la telemetría de uso, un problema al auditar no puede tumbar
     * la acción que el usuario acaba de hacer bien (si la contraseña ya cambió, cambió).
     */
    public static function record(User $user, string $event, ?string $context = null): ?self
    {
        try {
            return self::create([
                'tenant_id' => $user->tenant_id,
                'user_id' => $user->id,
                'event' => $event,
                'context' => $context,
            ]);
        } catch (\Throwable $e) {
            Log::error('security_events: no se pudo registrar', [
                'event' => $event,
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
