<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;

class CheckTenantActivation
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();
        
        // Solo aplicar a usuarios autenticados en el panel tenant
        if ($user && $user->tenant_id && !$user->tenant?->active) {
            // Verificar si la ruta actual es la página de activación pendiente
            $isActivationPage = $request->routeIs('filament.tenant.pages.activation-pending') ||
                               $request->is('tenant/activation-pending');
            
            // Verificar si es una ruta de autenticación de Filament
            $isAuthRoute = $request->is('tenant/login') ||
                          $request->is('tenant/register') ||
                          $request->is('tenant/logout');
            
            // Si no está en la página de activación y no es ruta de auth, redirigir
            if (!$isActivationPage && !$isAuthRoute) {
                // Usar URL directa para evitar problemas de rutas
                return redirect('/tenant/activation-pending');
            }
        }

        return $next($request);
    }
}