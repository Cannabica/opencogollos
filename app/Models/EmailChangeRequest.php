<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * Solicitud de cambio de email (doble opt-in).
 *
 * El email es la credencial de login y por eso el cambio no se aplica de una: se crea esta solicitud,
 * se manda el link a la dirección nueva y el cambio recién ocurre al confirmar. El token se guarda
 * hasheado; el valor plano existe sólo en el mail.
 */
class EmailChangeRequest extends Model
{
    public const UPDATED_AT = null;

    /** Cuánto vale el link. Corto a propósito: es una credencial viajando por mail. */
    public const VALID_HOURS = 2;

    protected $fillable = [
        'user_id',
        'new_email',
        'token_hash',
        'expires_at',
        'confirmed_at',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'confirmed_at' => 'datetime',
    ];

    public static function hash(string $token): string
    {
        return hash('sha256', $token);
    }

    /**
     * Crea la solicitud y devuelve el **token plano** (lo único que va al mail).
     *
     * Las solicitudes anteriores del mismo usuario se descartan: si alguien pide dos cambios, vale el
     * último pedido — no tiene sentido dejar links viejos dando vueltas.
     *
     * @return array{request: self, token: string}
     */
    public static function createFor(User $user, string $newEmail): array
    {
        self::query()->where('user_id', $user->id)->delete();

        $token = Str::random(64);

        $request = self::create([
            'user_id' => $user->id,
            'new_email' => $newEmail,
            'token_hash' => self::hash($token),
            'expires_at' => Carbon::now()->addHours(self::VALID_HOURS),
        ]);

        return ['request' => $request, 'token' => $token];
    }

    public static function findByToken(string $token): ?self
    {
        return self::query()
            ->where('token_hash', self::hash($token))
            ->whereNull('confirmed_at')
            ->first();
    }

    public function estaVigente(): bool
    {
        return $this->confirmed_at === null && $this->expires_at->isFuture();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
