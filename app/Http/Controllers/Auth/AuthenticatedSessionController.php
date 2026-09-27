<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * De todo el scaffold de Breeze, acá sólo queda el logout.
 *
 * El login (create/store) se eliminó el 2026-09-14 junto con el resto de la
 * superficie: lo sirve el panel Filament, que además rate-limita el intento.
 * Ver `routes/auth.php` y las tarjetas T10.x del board.
 */
class AuthenticatedSessionController extends Controller
{
    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}
