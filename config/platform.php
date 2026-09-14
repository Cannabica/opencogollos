<?php

/*
|--------------------------------------------------------------------------
| Plataforma — parametrización de marca (marca blanca)
|--------------------------------------------------------------------------
|
| TODOS los valores son `null` por defecto: una instalación nueva (self-hosted)
| no hereda NINGÚN dato de la operación de Cannabica. Cuando una clave es null
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

];
