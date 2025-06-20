<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class ApiServiceMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        // Add API service specific middleware logic here
        return $next($request);
    }
}