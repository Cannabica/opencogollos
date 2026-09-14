<?php

/*
|--------------------------------------------------------------------------
| Test Bootstrap
|--------------------------------------------------------------------------
|
| `vendor/` está symlinkeado al checkout principal
| (/mnt/datos/frankie/30-foundre/OpenIndoor/vendor). El autoloader de Composer
| calcula `$baseDir` como `dirname(dirname(__DIR__))` sobre el realpath de
| `vendor/composer`, por lo que los prefijos PSR-4 del proyecto (`App\`,
| `Tests\`, `Database\Factories\`, `Database\Seeders\`) apuntan al checkout
| principal en lugar de a este worktree. Consecuencia: `bootstrap/app.php` se
| carga desde el checkout principal y `base_path()` queda mal, así que las
| migraciones/rutas/config de ESTE worktree no se usan en los tests.
|
| Para que la suite corra contra el código de este worktree, re-registramos
| esos prefijos PSR-4 del proyecto apuntando a `dirname(__DIR__)`, y fijamos
| `APP_BASE_PATH` de forma explícita. En un checkout normal (vendor real, no
| symlinkeado) esto es un no-op: `dirname(__DIR__)` ya es el raíz del proyecto
| y el autoloader ya resuelve a `App\`, `Tests\`, etc. correctamente.
|
*/

$basePath = dirname(__DIR__);

$_ENV['APP_BASE_PATH'] = $basePath;
$_SERVER['APP_BASE_PATH'] = $basePath;
putenv('APP_BASE_PATH=' . $basePath);

require $basePath . '/vendor/autoload.php';

spl_autoload_register(function (string $class) use ($basePath): void {
    $prefixes = [
        'App\\' => $basePath . '/app/',
        'Database\\Factories\\' => $basePath . '/database/factories/',
        'Database\\Seeders\\' => $basePath . '/database/seeders/',
        'Tests\\' => $basePath . '/tests/',
    ];

    foreach ($prefixes as $prefix => $dir) {
        if (str_starts_with($class, $prefix)) {
            $file = $dir . str_replace('\\', '/', substr($class, strlen($prefix))) . '.php';

            if (is_file($file)) {
                require $file;
            }

            return;
        }
    }
}, true, true);
