<?php

namespace App\Http\Controllers;

use Illuminate\Http\Response;

class AuthCheckController extends Controller
{
    /**
     * Lightweight auth check for Caddy's forward_auth.
     * Returns 200 if authenticated, 401 if not.
     */
    public function check(): Response
    {
        if (auth()->check()) {
            return response('', 200);
        }

        return response('', 401);
    }
}
