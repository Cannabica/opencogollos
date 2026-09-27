<?php

namespace App\Providers;

use App\Http\Controllers\Api\ApiLoginController;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * The path to your application's "home" route.
     *
     * Typically, users are redirected here after authentication.
     *
     * @var string
     */
    public const HOME = '/tenant';

    /**
     * Define your route model bindings, pattern filters, and other route configuration.
     */
    public function boot(): void
    {
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });

        /*
         * Login de la API (POST /api/auth/login).
         *
         * No es una ruta de la app: la registra el paquete `rupadana/filament-api-service` y
         * queda SIN middleware, así que no hereda ni el `throttle:api` del grupo. Medido el
         * 2026-09-14: 65 intentos fallidos seguidos devolvían 65 x 401, ni un 429 — fuerza
         * bruta ilimitada contra una cuenta, mientras el login del panel corta a los 5.
         *
         * Dos techos, elegidos para NO romper una flota de dispositivos detrás de un mismo NAT:
         *   - 20/min por IP         → generoso para uso legítimo, frena el ataque simple.
         *   - 5/min por email + IP  → lo que importa: el ataque contra UNA cuenta desde UNA IP
         *                             se corta en el 6º intento.
         */
        RateLimiter::for('api-login', function (Request $request) {
            $email = strtolower((string) $request->input('email'));

            return [
                Limit::perMinute(20)->by($request->ip()),
                Limit::perMinute(5)->by($email . '|' . $request->ip()),
            ];
        });

        $this->routes(function () {
            Route::middleware('api')
                ->prefix('api')
                ->group(base_path('routes/api.php'));

            Route::middleware('web')
                ->group(base_path('routes/web.php'));
        });

        // La ruta del login de la API la crea el paquete y no expone un hook de middleware,
        // así que se lo colgamos acá una vez que todas las rutas están registradas.
        // Cubierto por tests/Feature/ApiLoginThrottleTest.php (sin esto, 65 intentos = 0 cortes).
        $this->app->booted(function () {
            foreach (Route::getRoutes() as $route) {
                if ($route->uri() === 'api/auth/login') {
                    $route->middleware('throttle:api-login');

                    // T10.6: la ACCIÓN también es del paquete, que no mira el estado del tenant y
                    // emitía el token con la ability comodín `['*']`. Se reemplaza por la nuestra
                    // (ApiLoginController): 403 si el tenant está inactivo + abilities acotadas.
                    // Garantía: tests/Feature/ApiLoginHardeningTest.php.
                    $route->setAction(array_merge($route->getAction(), [
                        'uses' => ApiLoginController::class . '@login',
                        'controller' => ApiLoginController::class . '@login',
                    ]));
                }
            }
        });
    }
}
