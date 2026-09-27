<?php

/*
|--------------------------------------------------------------------------
| Plataforma — parametrización de marca (marca blanca)
|--------------------------------------------------------------------------
|
| TODOS los valores son `null` por defecto: una instalación nueva (self-hosted)
| no hereda NINGÚN dato de una instalación concreta. Cuando una clave es null
| la UI simplemente OMITE el bloque que la usa (links de estado, comunidad,
| logos, footer, textos de Telegram/emails).
|
| Se configuran por variables de entorno `PLATFORM_*` (ver `.env.example`).
| Los valores de una instalación concreta NO se commitean nunca: viven en el
| `.env` del deploy (no en este repo).
|
*/

return [

    // Nombre de la marca / instalación. null => la UI no muestra marca propia.
    'brand_name' => env('PLATFORM_BRAND_NAME'),

    // Sitio público de la marca (home, web property).
    'site_url' => env('PLATFORM_SITE_URL'),

    // Página pública de estado del servicio (uptime / monitor).
    'status_page_url' => env('PLATFORM_STATUS_PAGE_URL'),

    // URL base de la plataforma (textos de Telegram, emails, enlaces).
    'platform_url' => env('PLATFORM_PLATFORM_URL'),

    'community' => [

        // Invitación al servidor de Discord de la comunidad.
        'discord_url' => env('PLATFORM_DISCORD_URL'),

        // Formulario / URL para reportar bugs y dejar feedback.
        'feedback_url' => env('PLATFORM_FEEDBACK_URL'),

    ],

    // Email de contacto administrativo (alta de superadmin, avisos).
    'admin_email' => env('PLATFORM_ADMIN_EMAIL'),

    // Username del bot de Telegram (sin @) para los textos de ayuda.
    'telegram_bot_username' => env('PLATFORM_TELEGRAM_BOT_USERNAME'),

    // Logo del header de los emails (URL o path público). null => el header
    // del email sale sin imagen (solo texto plano del nombre de la app).
    'mail_header_logo' => env('PLATFORM_MAIL_HEADER_LOGO'),

    // --- NO es una de las 9 keys de marca: bootstrap del seeder -------------
    // Fallback de `admin_email` para el SuperAdminSeeder: si PLATFORM_ADMIN_EMAIL no
    // está, el seeder usa ADMIN_EMAIL en vez de quedarse sin superadmin (y por lo
    // tanto sin acceso a /superadmin) en un `migrate:fresh --seed` de recovery.
    // Se lee por config y NO con `env()` dentro del seeder: en prod el entrypoint del
    // contenedor corre `config:cache`, y este valor tiene que quedar horneado en el
    // cache (decisión C3 de WS9/T9.5).
    'admin_email_fallback' => env('ADMIN_EMAIL'),

];
