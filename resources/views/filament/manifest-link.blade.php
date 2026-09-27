{{--
    Manifest de la PWA (WS4 · T4.6 · hallazgo H1 resuelto en T9.5).

    La ruta `manifest` (GET /manifest.json → ManifestController) devuelve el JSON
    dinamico con `name`/`short_name` = config('app.name'), asi que la misma
    instalacion ve el nombre de su producto. Lo que faltaba era DECLARARLO: el
    unico `rel="manifest"` vivia en resources/views/welcome.blade.php, una vista
    muerta sin referencias (la ruta `/` redirige a /tenant/login), por lo que
    ningun navegador lo descubria.

    Se registra por render hook en el HEAD de los paneles Filament (tenant y
    superadmin), que son las paginas que si se renderizan.
--}}
<link rel="manifest" href="{{ route('manifest') }}">
