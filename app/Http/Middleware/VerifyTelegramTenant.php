<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * Verifica que un update del webhook venga REALMENTE de Telegram.
 *
 * POR QUÉ (hallazgo 2026-09-14, tarjeta T10.7): `POST /api/telegram/webhook` estaba registrado sólo con
 * `api` (throttle) y **sin ninguna verificación**, y este middleware —que ya existía— **nunca estaba
 * aplicado a ninguna ruta** (en el Kernel sólo vivía el alias `verify.telegram.tenant`). Además pedía
 * una clave de config inexistente (`telegram.webhook_secret`), no importaba la clase que usaba
 * (`TenantTokenService`) y su verificación era *fail open* (sin header, seguía de largo). Resultado:
 * cualquiera podía hacer POST de un update falso eligiendo el `from.id`, y las acciones del bot corrían
 * **como el usuario que el atacante indicara**.
 *
 * CÓMO: Telegram agrega el header `X-Telegram-Bot-Api-Secret-Token` en cada update **si el webhook se
 * registró con `secret_token`** (ver `WebhookSetupCommand`). Acá se exige ese header y se compara en
 * tiempo constante (`hash_equals`).
 *
 * FAIL CLOSED: si `TELEGRAM_SECRET_TOKEN` no está configurado se rechaza con **503** y se loguea un
 * error — igual que el webhook del bot admin. Un webhook sin secret es un webhook forjable: mejor caído
 * que abierto. Por eso este cambio se aplica **junto con** la carga del secret en el deploy y el
 * re-registro del webhook (`php artisan telegram:webhook-setup`).
 */
class VerifyTelegramTenant
{
    public function handle(Request $request, Closure $next): Response
    {
        $esperado = (string) config('telegram.tenant_secret', '');

        if ($esperado === '') {
            Log::error('Webhook del bot tenant: TELEGRAM_SECRET_TOKEN no está configurado. Update rechazado (fail closed).');

            return response('Service Unavailable', 503);
        }

        $recibido = (string) $request->header('X-Telegram-Bot-Api-Secret-Token', '');

        if ($recibido === '' || ! hash_equals($esperado, $recibido)) {
            Log::warning('Webhook del bot tenant: secret inválido', [
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

            return response('Unauthorized', 401);
        }

        return $next($request);
    }
}
