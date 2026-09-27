<?php

namespace App\Http\Controllers\Api;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Rupadana\ApiService\Http\Requests\LoginRequest;

/**
 * Login de la API (POST /api/auth/login).
 *
 * Reemplaza la acción del controller del paquete `rupadana/filament-api-service`
 * (`vendor/rupadana/filament-api-service/src/Http/Controllers/AuthController.php`), que tenía
 * dos problemas:
 *
 *   1. Emitía el token con la ability comodín `['*']`.
 *   2. No miraba el estado del tenant: un cultivador con la cuenta **inactiva** obtenía token
 *      igual, con credenciales válidas (el panel sí lo filtra, vía `CheckTenantActivation`).
 *
 * La ruta la registra el paquete y no expone hook, así que el reemplazo de la acción se hace en
 * `RouteServiceProvider` (el mismo lugar donde ya se le cuelga el `throttle`).
 * Garantía: `tests/Feature/ApiLoginHardeningTest.php`.
 */
class ApiLoginController extends Controller
{
    /**
     * Abilities que recibe el token del login.
     *
     * Hoy **ningún** endpoint del repo chequea abilities (`tokenCan()`), así que esto no cambia el
     * comportamiento actual: es el default acotado para cuando se empiecen a chequear. Si aparece
     * un caso de uso que necesite más, se agrega acá explícito — nunca de vuelta a `['*']`.
     */
    public const ABILITIES = ['api:read', 'api:write'];

    /**
     * @return \Illuminate\Http\JsonResponse
     */
    public function login(LoginRequest $request)
    {
        if (! Auth::validate($request->validated())) {
            return response()->json(
                [
                    'success' => false,
                    'message' => 'The provided credentials are incorrect.',
                ],
                401
            );
        }

        /** @var User $user */
        $user = Auth::getLastAttempted();

        // Credenciales correctas pero el tenant está inactivo: no se emite token. Se responde 403 y no
        // 401 para que el cliente pueda distinguir "clave mal" de "cuenta dada de baja" (el intento ya
        // está autenticado, así que no se filtra nada que el que pregunta no sepa).
        //
        // Se busca el tenant por id en vez de usar `$user->tenant`: las relaciones del modelo no
        // declaran su tipo de retorno, así que larastan no puede validar la propiedad (y tiparlas
        // destapa ~9 errores de otros modelos: es una tarjeta aparte, no de este fix).
        $tenant = $user->tenant_id ? Tenant::find($user->tenant_id) : null;

        if ($tenant && ! $tenant->active) {
            return response()->json(
                [
                    'success' => false,
                    'message' => 'La cuenta del cultivador está inactiva.',
                ],
                403
            );
        }

        return response()->json(
            [
                'success' => true,
                'message' => 'Login success.',
                'token' => $user->createToken($request->header('User-Agent'), self::ABILITIES)->plainTextToken,
            ],
            201
        );
    }
}
