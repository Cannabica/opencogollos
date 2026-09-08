<?php

namespace App\Http\Middleware;

use App\Models\UsageEvent;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class LogUsageMiddleware
{
    /**
     * Registra una fila en usage_events por cada página vista (GET con
     * status 2xx) dentro de los paneles Filament autenticados.
     *
     * Se deriva todo del route_name de Filament (estable, sin IDs de
     * recursos): filament.{panel}.{resources|pages}.{slug}[.{action}].
     * Las rutas de auth (login/register/logout) no matchean el patrón
     * resources|pages y los POST de Livewire no pasan por este stack.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $user = $request->user();
        $routeName = $request->route()?->getName();

        if (
            $user === null
            || $request->getMethod() !== 'GET'
            || ! $response->isSuccessful()
            || ! is_string($routeName)
            || ! preg_match('/^filament\.(tenant|superadmin)\.(resources|pages)\./', $routeName)
        ) {
            return $response;
        }

        $parts = explode('.', $routeName);
        $panel = $parts[1] ?? null;
        $type = $parts[2] ?? null;
        $module = $parts[3] ?? null;
        $action = $type === 'resources' ? ($parts[4] ?? 'index') : null;

        try {
            UsageEvent::create([
                'tenant_id' => $user->tenant_id,
                'user_id' => $user->id,
                'panel' => $panel,
                'route_name' => $routeName,
                'module' => $module,
                'action' => $action,
            ]);
        } catch (\Throwable $e) {
            // La telemetría nunca debe tumbar una request.
            Log::error('usage_events: no se pudo registrar', [
                'route_name' => $routeName,
                'error' => $e->getMessage(),
            ]);
        }

        return $response;
    }
}
