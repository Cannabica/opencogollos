<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;

class ManifestController extends Controller
{
    /**
     * Manifest de la PWA (épica open-core, WS4 · T4.6).
     *
     * Antes era `public/manifest.json` con el nombre de una instalación concreta
     * clavado en el archivo. Ahora el nombre sale de `config('platform.brand_name')`
     * con fallback a `config('app.name')` (decisión C4e de WS9/T9.5: UNA sola key de
     * nombre visible): una instalación que configura su marca (`PLATFORM_BRAND_NAME`)
     * la ve también en la PWA; una que no la configura muestra el nombre del producto.
     */
    public function __invoke(): JsonResponse
    {
        $name = (string) (config('platform.brand_name') ?: config('app.name'));

        return response()->json([
            'name' => $name,
            'short_name' => $name,
            'start_url' => '/',
            'display' => 'standalone',
            'icons' => [
                ['src' => '/android-icon-36x36.png', 'sizes' => '36x36', 'type' => 'image/png', 'density' => '0.75'],
                ['src' => '/android-icon-48x48.png', 'sizes' => '48x48', 'type' => 'image/png', 'density' => '1.0'],
                ['src' => '/android-icon-72x72.png', 'sizes' => '72x72', 'type' => 'image/png', 'density' => '1.5'],
                ['src' => '/android-icon-96x96.png', 'sizes' => '96x96', 'type' => 'image/png', 'density' => '2.0'],
                ['src' => '/android-icon-144x144.png', 'sizes' => '144x144', 'type' => 'image/png', 'density' => '3.0'],
                ['src' => '/android-icon-192x192.png', 'sizes' => '192x192', 'type' => 'image/png', 'density' => '4.0'],
            ],
        ], 200, [], JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT)
            ->header('Content-Type', 'application/manifest+json');
    }
}
