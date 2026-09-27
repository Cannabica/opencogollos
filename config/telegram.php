<?php

use Telegram\Bot\Commands\HelpCommand;

return [
    /*
    |--------------------------------------------------------------------------
    // | Your Telegram Bots
    |--------------------------------------------------------------------------
    | You may use multiple bots at once using the manager class. Each bot
    | that you own should be configured here.
    |
    | Here are each of the telegram bots config parameters.
    |
    | Supported Params:
    |
    | - name: The *personal* name you would like to refer to your bot as.
    |
    |       - token:    Your Telegram Bot's Access Token.
                        Refer for more details: https://core.telegram.org/bots#botfather
    |                   Example: (string) '123456:ABC-DEF1234ghIkl-zyx57W2v1u123ew11'.
    |
    |       - commands: (Optional) Commands to register for this bot,
    |                   Supported Values: "Command Group Name", "Shared Command Name", "Full Path to Class".
    |                   Default: Registers Global Commands.
    |                   Example: (array) [
    |                       'admin', // Command Group Name.
    |                       'status', // Shared Command Name.
    |                       Acme\Project\Commands\BotFather\HelloCommand::class,
    |                       Acme\Project\Commands\BotFather\ByeCommand::class,
    |             ]
    */
    'bots' => [
        'default' => [
            'token' => env('TELEGRAM_BOT_TOKEN'),
            'webhook_url' => env('TELEGRAM_WEBHOOK_URL', env('APP_URL') . '/api/telegram/webhook/'),
            'allowed_updates' => ['message', 'callback_query'],
            'commands' => [
                'start',
                'auth',
                'tenantinfo',
                'indoordetails',
                'plants',
                'seedslist',
                'repetirriego',
                'plantdetails',
                'actionslist',
                'actiondetails',
                'callback',
                'photo'
            ],
        ],

        'admin' => [
            'token' => env('TELEGRAM_ADMIN_BOT_TOKEN'),
            'webhook_url' => env('TELEGRAM_ADMIN_WEBHOOK_URL', env('APP_URL') . '/api/telegram/admin/webhook/'),
            'allowed_updates' => ['message'],
            'commands' => [
                'admin_start',
                'admin_estado',
                'admin_tenants',
                'admin_tenant',
                'admin_metricas',
                'admin_pendientes',
                'admin_activar',
                'admin_desactivar',
                'admin_confirmar',
                'admin_cancelar',
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Default Bot Name
    |--------------------------------------------------------------------------
    |
    | Here you may specify which of the bots you wish to use as
    | your default bot for regular use.
    |
    */
    'default' => 'default',

    /*
    |--------------------------------------------------------------------------
    | Admin Bot Security
    |--------------------------------------------------------------------------
    |
    | Secret token que Telegram incluye en el header
    | X-Telegram-Bot-Api-Secret-Token al llamar al webhook del bot admin.
    | Sin este valor el webhook admin rechaza todo (fail closed).
    |
    */
    'admin_secret' => env('TELEGRAM_ADMIN_SECRET_TOKEN'),

    /*
     * Secret del webhook del bot TENANT (el que usan los usuarios). Telegram lo devuelve en el header
     * `X-Telegram-Bot-Api-Secret-Token` de cada update si el webhook se registró con él
     * (ver WebhookSetupCommand). Lo verifica el middleware VerifyTelegramTenant.
     * Sin esto el webhook es forjable: cualquiera puede mandar un update falso con el from.id que
     * quiera y el bot actúa como ese usuario (tarjeta T10.7). Va en el .env del server, nunca en el repo.
     */
    'tenant_secret' => env('TELEGRAM_SECRET_TOKEN'),

    /*
    |--------------------------------------------------------------------------
    | Admin Bot Allowed User Ids
    |--------------------------------------------------------------------------
    |
    | Ids numéricos de Telegram autorizados a usar el bot de administración
    | (separados por coma). Solo el superadmin de la plataforma.
    |
    */
    'admin_allowed_user_ids' => env('TELEGRAM_ADMIN_ALLOWED_USER_IDS', ''),

    /*
    |--------------------------------------------------------------------------
    | Admin Bot Digest
    |--------------------------------------------------------------------------
    |
    | Habilita/deshabilita el resumen proactivo (admin:digest) que le manda
    | novedades al superadmin por Telegram.
    |
    */
    'admin_digest_enabled' => env('TELEGRAM_ADMIN_DIGEST_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Asynchronous Requests [Optional]
    |--------------------------------------------------------------------------
    |
    | When set to True, All the requests would be made non-blocking (Async).
    |
    | Default: false
    | Possible Values: (Boolean) "true" OR "false"
    |
    */
    'async_requests' => env('TELEGRAM_ASYNC_REQUESTS', false),

    /*
    |--------------------------------------------------------------------------
    | HTTP Client Handler [Optional]
    |--------------------------------------------------------------------------
    |
    | If you'd like to use a custom HTTP Client Handler.
    | Should be an instance of \Telegram\Bot\HttpClients\HttpClientInterface
    |
    | Default: GuzzlePHP
    |
    */
    'http_client_handler' => null,

    /*
    |--------------------------------------------------------------------------
    | Base Bot Url [Optional]
    |--------------------------------------------------------------------------
    |
    | If you'd like to use a custom Base Bot Url.
    | Should be a local bot api endpoint or a proxy to the telegram api endpoint
    |
    | Default: https://api.telegram.org/bot
    |
    */
    'base_bot_url' => null,

    /*
    |--------------------------------------------------------------------------
    | Resolve Injected Dependencies in commands [Optional]
    |--------------------------------------------------------------------------
    |
    | Using Laravel's IoC container, we can easily type hint dependencies in
    | our command's constructor and have them automatically resolved for us.
    |
    | Default: true
    | Possible Values: (Boolean) "true" OR "false"
    |
    */
    'resolve_command_dependencies' => true,

    /*
    |--------------------------------------------------------------------------
    | Register Telegram Global Commands [Optional]
    |--------------------------------------------------------------------------
    |
    | If you'd like to use the SDK's built in command handler system,
    | You can register all the global commands here.
    |
    | Global commands will apply to all the bots in system and are always active.
    |
    | The command class should extend the \Telegram\Bot\Commands\Command class.
    |
    | Default: The SDK registers, a help command which when a user sends /help
    | will respond with a list of available commands and description.
    |
    */
    'commands' => [
        HelpCommand::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Command Groups [Optional]
    |--------------------------------------------------------------------------
    |
    | You can organize a set of commands into groups which can later,
    | be re-used across all your bots.
    |
    | You can create 4 types of groups:
    | 1. Group using full path to command classes.
    | 2. Group using shared commands: Provide the key name of the shared command
    | and the system will automatically resolve to the appropriate command.
    | 3. Group using other groups of commands: You can create a group which uses other
    | groups of commands to bundle them into one group.
    | 4. You can create a group with a combination of 1, 2 and 3 all together in one group.
    |
    | Examples shown below are by the group type for you to understand each of them.
    */
    'command_groups' => [
        /* // Group Type: 1
           'commmon' => [
                Acme\Project\Commands\TodoCommand::class,
                Acme\Project\Commands\TaskCommand::class,
           ],
        */

        /* // Group Type: 2
           'subscription' => [
                'start', // Shared Command Name.
                'stop', // Shared Command Name.
           ],
        */

        /* // Group Type: 3
            'auth' => [
                Acme\Project\Commands\LoginCommand::class,
                Acme\Project\Commands\SomeCommand::class,
            ],

            'stats' => [
                Acme\Project\Commands\UserStatsCommand::class,
                Acme\Project\Commands\SubscriberStatsCommand::class,
                Acme\Project\Commands\ReportsCommand::class,
            ],

            'admin' => [
                'auth', // Command Group Name.
                'stats' // Command Group Name.
            ],
        */

        /* // Group Type: 4
           'myBot' => [
                'admin', // Command Group Name.
                'subscription', // Command Group Name.
                'status', // Shared Command Name.
                'Acme\Project\Commands\BotCommand' // Full Path to Command Class.
           ],
        */
    ],

    /*
    |--------------------------------------------------------------------------
    | Shared Commands [Optional]
    |--------------------------------------------------------------------------
    |
    | Shared commands let you register commands that can be shared between,
    | one or more bots across the project.
    |
    | This will help you prevent from having to register same set of commands,
    | for each bot over and over again and make it easier to maintain them.
    |
    | Shared commands are not active by default, You need to use the key name to register them,
    | individually in a group of commands or in bot commands.
    | Think of this as a central storage, to register, reuse and maintain them across all bots.
    |
    */
    'shared_commands' => [
        'start' => App\Telegram\Commands\StartCommand::class,
        'auth' => App\Telegram\Commands\AuthCommand::class,
        'tenantinfo' => App\Telegram\Commands\TenantInfoCommand::class,
        'indoordetails' => App\Telegram\Commands\IndoorDetailsCommand::class,
        'plants' => App\Telegram\Commands\PlantsListCommand::class,
        'plantdetails' => App\Telegram\Commands\PlantDetailsCommand::class,
        'seedslist' => App\Telegram\Commands\SeedsListCommand::class,
        'actionslist' => App\Telegram\Commands\ActionsListCommand::class,
        'actiondetails' => App\Telegram\Commands\ActionDetailsCommand::class,
        'photo' => App\Telegram\Commands\PhotoHandlerCommand::class,
        'callback' => App\Telegram\Commands\CallbackHandlerCommand::class,
        'repetirriego' => App\Telegram\Commands\RepeatLastIrrigationCommand::class,

        // Comandos del bot de administración (superadmin)
        'admin_start' => App\Telegram\Admin\Commands\StartCommand::class,
        'admin_estado' => App\Telegram\Admin\Commands\EstadoCommand::class,
        'admin_tenants' => App\Telegram\Admin\Commands\TenantsCommand::class,
        'admin_tenant' => App\Telegram\Admin\Commands\TenantCommand::class,
        'admin_metricas' => App\Telegram\Admin\Commands\MetricasCommand::class,
        'admin_pendientes' => App\Telegram\Admin\Commands\PendientesCommand::class,
        'admin_activar' => App\Telegram\Admin\Commands\ActivarCommand::class,
        'admin_desactivar' => App\Telegram\Admin\Commands\DesactivarCommand::class,
        'admin_confirmar' => App\Telegram\Admin\Commands\ConfirmarCommand::class,
        'admin_cancelar' => App\Telegram\Admin\Commands\CancelarCommand::class,
    ],
];
