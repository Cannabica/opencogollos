<?php

namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Illuminate\Http\Request;

class Authenticate extends Middleware
{
    /**
     * Get the path the user should be redirected to when they are not authenticated.
     */
    protected function redirectTo(Request $request): ?string
    {
        if ($request->expectsJson()) {
            return null;
        }

        // Determinar la ruta de redirección basada en la URL actual
        $path = $request->path();
        if (str_starts_with($path, 'superadmin')) {
            return '/superadmin/login';
        }
        if (str_starts_with($path, 'tenant')) {
            return '/tenant/login';
        }

        return '/tenant/login';
    }
}
