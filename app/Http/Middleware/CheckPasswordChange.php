<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;

class CheckPasswordChange
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();
        
        // Only apply to authenticated users
        if ($user && $user->force_password_change) {
            // Check if the current route is the password change page or authentication routes
            $isPasswordChangePage = $request->routeIs('filament.tenant.pages.password-change') ||
                                   $request->is('tenant/password-change') ||
                                   $request->routeIs('password-change');
            
            $isAuthRoute = $request->is('tenant/login') ||
                          $request->is('tenant/register') ||
                          $request->is('tenant/logout') ||
                          $request->is('tenant/password/reset*');
            
            // If not on the password change page and not an auth route, redirect to password change
            if (!$isPasswordChangePage && !$isAuthRoute) {
                // Use Filament route for password change
                return redirect()->route('filament.tenant.pages.password-change');
            }
        }

        return $next($request);
    }
}