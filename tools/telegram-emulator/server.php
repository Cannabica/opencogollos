<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Emulador HTTP del Bot API de Telegram — OpenIndoor
|--------------------------------------------------------------------------
|
| POR QUÉ: los bots de OpenIndoor se prueban hoy (a) con mocks que no tocan
| HTTP o (b) apuntando el webhook al Telegram real por ngrok. Lo primero no
| verifica que el payload salga bien; lo segundo depende de internet, de un
| bot vivo y de que el usuario abra el chat.
|
| QUÉ HACE: implementa el subconjunto del Bot API que la app usa, 100%
| offline (cero llamadas salientes), y agrega un plano de control para
| (1) inyectar updates entrantes —los POSTea al webhook registrado, igual que
| Telegram— y (2) inspeccionar los llamados SALIENTES de la app (sendMessage,
| sendPhoto, etc.), que es lo que hoy nadie verifica.
|
| CÓMO SE ENCHUFA: el SDK irazasyed/telegram-bot-sdk acepta
| `config('telegram.base_bot_url')`; el cliente arma la URL como
| `base_bot_url . TOKEN . '/' . endpoint` (TelegramClient::prepareRequest).
| Apuntando base_bot_url a este server, TODOS los bots de la app hablan acá.
|
| SUPERFICIE (paths):
|   POST/GET /bot<TOKEN>/<metodo>   -> Bot API emulado
|   GET      /_emulator/health
|   GET      /_emulator/outbound    -> llamados salientes capturados
|   GET      /_emulator/webhooks    -> webhooks registrados por setWebhook
|   POST     /_emulator/inject      -> inyecta un update y lo entrega al webhook
|   POST     /_emulator/reset
|   GET      /_emulator/state       -> todo el estado
|
| SIN DEPENDENCIAS: sólo stdlib de PHP 8.3. Se corre con el server embebido:
|   php -S 127.0.0.1:8082 tools/telegram-emulator/server.php
|
| OJO: el server embebido de PHP NO conserva estado entre requests: cada
| request re-ejecuta el script. Por eso el estado (webhooks + salientes) vive
| en un archivo JSON con flock. Es lo que permite que el plano de control vea
| lo que envió la app en otro request.
*/

final class TelegramEmulator
{
    private const BOT_USER_ID = 987654321;

    private string $stateFile;

    private string $bindHost;

    private int $bindPort;

    public function __construct(string $stateFile, string $bindHost, int $bindPort)
    {
        $this->stateFile = $stateFile;
        $this->bindHost = $bindHost;
        $this->bindPort = $bindPort;
    }

    public function handle(): void
    {
        $path = (string) parse_url((string) ($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH);
        $httpMethod = strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'));

        try {
            if (str_starts_with($path, '/_emulator')) {
                $this->handleControl($httpMethod, $path);

                return;
            }

            if (preg_match('#^/(?:bot)?([^/]+)/([A-Za-z0-9_]+)/?$#', $path, $matches) === 1) {
                $this->handleBotApi($httpMethod, $matches[1], $matches[2]);

                return;
            }

            $this->json(['ok' => false, 'error_code' => 404, 'description' => 'Not Found'], 404);
        } catch (Throwable $e) {
            $this->json([
                'ok' => false,
                'error_code' => 500,
                'description' => 'emulator error: ' . $e->getMessage(),
            ], 500);
        }
    }

    // -----------------------------------------------------------------
    // Plano de control
    // -----------------------------------------------------------------

    private function handleControl(string $httpMethod, string $path): void
    {
        switch (rtrim($path, '/')) {
            case '/_emulator/health':
                $this->json([
                    'ok' => true,
                    'service' => 'openindoor-telegram-emulator',
                    'php' => PHP_VERSION,
                    'listen' => $this->bindHost . ':' . $this->bindPort,
                    'state_file' => $this->stateFile,
                    'webhooks' => array_keys($this->stateRead()['webhooks'] ?? []),
                ]);

                return;

            case '/_emulator/outbound':
                $calls = $this->stateRead()['outbound'] ?? [];
                $token = isset($_GET['token']) ? (string) $_GET['token'] : null;
                $method = isset($_GET['method']) ? (string) $_GET['method'] : null;

                $filtered = array_values(array_filter($calls, function (array $call) use ($token, $method): bool {
                    if ($token !== null && ($call['token'] ?? null) !== $token) {
                        return false;
                    }

                    return $method === null || ($call['method'] ?? null) === $method;
                }));

                $this->json(['ok' => true, 'count' => count($filtered), 'calls' => $filtered]);

                return;

            case '/_emulator/webhooks':
                $this->json(['ok' => true, 'webhooks' => $this->stateRead()['webhooks'] ?? []]);

                return;

            case '/_emulator/webhook':
                // Registrar el webhook desde acá es la vía NORMAL contra el emulador:
                // el SDK valida que la URL sea HTTPS (Methods/Update.php:184), así que
                // `php artisan telegram:webhook:setup` NO puede apuntar a un http local.
                if ($httpMethod !== 'POST') {
                    $this->json(['ok' => false, 'description' => 'usar POST'], 405);

                    return;
                }

                $payload = $this->readPayload();
                $token = (string) ($payload['token'] ?? '');

                if ($token === '' || empty($payload['url'])) {
                    $this->json(['ok' => false, 'description' => 'hacen falta token y url'], 422);

                    return;
                }

                $this->registerWebhook($token, $payload);

                $this->json([
                    'ok' => true,
                    'token' => $token,
                    'url' => (string) $payload['url'],
                    'with_secret' => ! empty($payload['secret_token']),
                ]);

                return;

            case '/_emulator/state':
                $state = $this->stateRead();
                $state['outbound_count'] = count($state['outbound'] ?? []);
                $this->json(['ok' => true, 'state' => $state]);

                return;

            case '/_emulator/inject':
                if ($httpMethod !== 'POST') {
                    $this->json(['ok' => false, 'description' => 'usar POST'], 405);

                    return;
                }

                $this->json($this->inject($this->readPayload()), 200);

                return;

            case '/_emulator/reset':
                if ($httpMethod !== 'POST') {
                    $this->json(['ok' => false, 'description' => 'usar POST'], 405);

                    return;
                }

                $this->stateWrite(['webhooks' => [], 'outbound' => [], 'pending_updates' => [], 'next_message_id' => 1]);
                $this->log('reset del estado');

                $this->json(['ok' => true]);

                return;

            default:
                $this->json(['ok' => false, 'description' => 'control desconocido: ' . $path], 404);
        }
    }

    /**
     * Inyecta un update y lo entrega al webhook registrado para ese bot,
     * exactamente como haría Telegram (POST JSON + header de secret token).
     */
    private function inject(array $payload): array
    {
        $state = $this->stateRead();

        $token = $payload['token'] ?? $state['last_token'] ?? null;

        if ($token === null || ! isset($state['webhooks'][$token])) {
            return [
                'ok' => false,
                'description' => 'no hay webhook registrado; llamá setWebhook primero',
                'webhooks' => array_keys($state['webhooks'] ?? []),
            ];
        }

        $webhook = $state['webhooks'][$token];

        $update = $payload['update'] ?? null;

        if (! is_array($update)) {
            // Atajo cómodo: /_emulator/inject {"text":"/estado","from_id":123,"chat_id":123}
            $update = $this->buildMessageUpdate($payload);
        }

        if (! isset($update['update_id'])) {
            $update['update_id'] = (int) ($state['next_update_id'] ?? 1);
            $state['next_update_id'] = $update['update_id'] + 1;
            $this->stateWrite($state);
        }

        $headers = ['Content-Type: application/json'];

        if (! empty($webhook['secret_token'])) {
            $headers[] = 'X-Telegram-Bot-Api-Secret-Token: ' . $webhook['secret_token'];
        }

        [$status, $body, $error] = $this->postWebhook((string) $webhook['url'], $headers, $update);

        $this->log(sprintf('inject -> %s | HTTP %s', $webhook['url'], $status ?? 'ERR'));

        return [
            'ok' => $error === null && $status !== null && $status >= 200 && $status < 300,
            'update_id' => $update['update_id'],
            'delivered_to' => $webhook['url'],
            'with_secret' => ! empty($webhook['secret_token']),
            'status' => $status,
            'response' => $body,
            'error' => $error,
        ];
    }

    private function buildMessageUpdate(array $payload): array
    {
        $chatId = (int) ($payload['chat_id'] ?? $payload['from_id'] ?? 424242);
        $fromId = (int) ($payload['from_id'] ?? $chatId);
        $text = (string) ($payload['text'] ?? '/start');

        $message = [
            'message_id' => (int) ($payload['message_id'] ?? 1),
            'date' => time(),
            'text' => $text,
            'from' => [
                'id' => $fromId,
                'is_bot' => false,
                'first_name' => (string) ($payload['first_name'] ?? 'Tester'),
                'language_code' => 'es',
            ],
            'chat' => [
                'id' => $chatId,
                'type' => 'private',
                'first_name' => (string) ($payload['first_name'] ?? 'Tester'),
            ],
        ];

        // La entidad bot_command tiene que cubrir SOLO el comando: el SDK saca el
        // nombre con substr(texto, offset+1, length-1). Si length cubriera el
        // argumento, el "comando" sale "auth <token>" y cae en HelpCommand.
        if (str_starts_with($text, '/')) {
            $command = preg_split('/\s+/', $text)[0] ?? $text;
            $message['entities'] = [[
                'offset' => 0,
                'length' => strlen($command),
                'type' => 'bot_command',
            ]];
        }

        return ['message' => $message];
    }

    // -----------------------------------------------------------------
    // Bot API emulado
    // -----------------------------------------------------------------

    private function handleBotApi(string $httpMethod, string $token, string $endpoint): void
    {
        $params = array_merge($_GET, $this->readPayload());

        $result = $this->dispatch($token, $endpoint, $params);

        if ($result === null) {
            $this->json(['ok' => false, 'error_code' => 404, 'description' => 'Not Found'], 404);

            return;
        }

        // Los salientes se registran SIEMPRE (aunque el método no toque estado),
        // que es justamente lo que los tests quieren ver.
        $this->recordOutbound($token, $endpoint, $params);

        $this->log(sprintf('%-24s token=%s', $endpoint, $token));

        $this->json(['ok' => true, 'result' => $result]);
    }

    private function dispatch(string $token, string $endpoint, array $params)
    {
        $chatId = $params['chat_id'] ?? null;
        $messageId = $this->nextMessageId();

        switch ($endpoint) {
            case 'getMe':
                return [
                    'id' => self::BOT_USER_ID,
                    'is_bot' => true,
                    'first_name' => 'OpenIndoor Emulador',
                    'username' => 'openindoor_emulador_bot',
                    'can_join_groups' => true,
                    'can_read_all_group_messages' => false,
                    'supports_inline_queries' => false,
                ];

            case 'sendMessage':
                return [
                    'message_id' => $messageId,
                    'from' => $this->botUser(),
                    'chat' => $this->chatObject($chatId, $params),
                    'date' => time(),
                    'text' => (string) ($params['text'] ?? ''),
                ];

            case 'sendPhoto':
                return [
                    'message_id' => $messageId,
                    'from' => $this->botUser(),
                    'chat' => $this->chatObject($chatId, $params),
                    'date' => time(),
                    'caption' => (string) ($params['caption'] ?? ''),
                    'photo' => [[
                        'file_id' => 'emulador-foto-' . $messageId,
                        'file_unique_id' => 'emulador-' . $messageId,
                        'width' => 1,
                        'height' => 1,
                        'file_size' => 1024,
                    ]],
                ];

            case 'editMessageText':
                return [
                    'message_id' => (int) ($params['message_id'] ?? $messageId),
                    'from' => $this->botUser(),
                    'chat' => $this->chatObject($chatId, $params),
                    'date' => time(),
                    'text' => (string) ($params['text'] ?? ''),
                ];

            case 'sendChatAction':
            case 'answerCallbackQuery':
            case 'deleteMessage':
            case 'pinChatMessage':
            case 'unpinChatMessage':
            case 'setMyCommands':
            case 'deleteMyCommands':
            case 'setMessageReaction':
            case 'close':
            case 'logOut':
                return true;

            case 'getMyCommands':
            case 'getUpdates':
                return [];

            case 'setWebhook':
                $this->registerWebhook($token, $params);

                return true;

            case 'deleteWebhook':
                $state = $this->stateRead();
                unset($state['webhooks'][$token]);
                $this->stateWrite($state);
                $this->log('deleteWebhook token=' . $token);

                return true;

            case 'getWebhookInfo':
                $webhook = $this->stateRead()['webhooks'][$token] ?? null;

                return [
                    'url' => (string) ($webhook['url'] ?? ''),
                    'has_custom_certificate' => false,
                    'pending_update_count' => 0,
                    'allowed_updates' => $webhook['allowed_updates'] ?? [],
                    'max_connections' => (int) ($webhook['max_connections'] ?? 40),
                ];

            case 'getFile':
                return [
                    'file_id' => (string) ($params['file_id'] ?? 'emulador-file'),
                    'file_unique_id' => 'emulador-file',
                    'file_size' => 1024,
                    'file_path' => 'files/emulador-archivo.txt',
                ];

            default:
                return null;
        }
    }

    private function registerWebhook(string $token, array $params): void
    {
        $state = $this->stateRead();

        $state['webhooks'][$token] = [
            'url' => (string) ($params['url'] ?? ''),
            'secret_token' => (string) ($params['secret_token'] ?? ''),
            'allowed_updates' => $params['allowed_updates'] ?? [],
            'max_connections' => (int) ($params['max_connections'] ?? 40),
            'registered_at' => date(DATE_ATOM),
        ];
        $state['last_token'] = $token;

        $this->stateWrite($state);

        $this->log(sprintf('setWebhook token=%s url=%s', $token, $state['webhooks'][$token]['url']));
    }

    private function botUser(): array
    {
        return [
            'id' => self::BOT_USER_ID,
            'is_bot' => true,
            'first_name' => 'OpenIndoor Emulador',
            'username' => 'openindoor_emulador_bot',
        ];
    }

    private function chatObject(mixed $chatId, array $params): array
    {
        $allowed = ['title', 'username', 'first_name', 'last_name'];

        if (is_string($chatId) && str_starts_with($chatId, '@')) {
            return ['id' => -1000000000000 - abs(crc32($chatId) % 100000), 'type' => 'channel', 'username' => ltrim($chatId, '@')];
        }

        $chat = ['id' => is_numeric($chatId) ? (int) $chatId : 0, 'type' => 'private'];

        foreach ($allowed as $key) {
            if (isset($params[$key])) {
                $chat[$key] = $params[$key];
            }
        }

        return $chat;
    }

    private function nextMessageId(): int
    {
        $state = $this->stateRead();
        $id = (int) ($state['next_message_id'] ?? 1);
        $state['next_message_id'] = $id + 1;
        $this->stateWrite($state);

        return $id;
    }

    private function recordOutbound(string $token, string $endpoint, array $params): void
    {
        $state = $this->stateRead();

        $state['outbound'][] = [
            'ts' => date(DATE_ATOM),
            'token' => $token,
            'method' => $endpoint,
            'params' => $this->sanitize($params),
        ];

        $state['last_token'] = $token;
        $this->stateWrite($state);
    }

    /** Los recursos subidos (fopen streams) no se pueden serializar a JSON. */
    private function sanitize(array $params): array
    {
        $clean = [];

        foreach ($params as $key => $value) {
            if (is_resource($value)) {
                $clean[$key] = '<recurso>';

                continue;
            }

            if (is_array($value)) {
                $clean[$key] = $this->sanitize($value);

                continue;
            }

            $clean[$key] = $value;
        }

        return $clean;
    }

    // -----------------------------------------------------------------
    // HTTP / estado
    // -----------------------------------------------------------------

    private function readPayload(): array
    {
        $raw = file_get_contents('php://input') ?: '';

        if ($raw === '') {
            return $_POST;
        }

        $decoded = json_decode($raw, true);

        if (is_array($decoded)) {
            return $decoded;
        }

        $parsed = [];
        parse_str($raw, $parsed);

        return $parsed !== [] ? $parsed : $_POST;
    }

    /**
     * @return array{0: int|null, 1: string, 2: string|null}
     */
    private function postWebhook(string $url, array $headers, array $update): array
    {
        // Contra un webhook https con certificado propio (Caddy `tls internal`,
        // mkcert, etc.) hay que decirle a PHP con qué CA validar. Vacío = la CA
        // del sistema (caso de un webhook realmente https de producción).
        $ssl = [
            'verify_peer' => true,
            'verify_peer_name' => true,
        ];

        $caFile = getenv('TELEGRAM_EMULATOR_CA_FILE') ?: '';
        $insecure = filter_var(getenv('TELEGRAM_EMULATOR_TLS_INSECURE') ?: '0', FILTER_VALIDATE_BOOL);

        if ($caFile !== '') {
            $ssl['cafile'] = $caFile;
        }

        if ($insecure) {
            $ssl['verify_peer'] = false;
            $ssl['verify_peer_name'] = false;
        }

        $context = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => implode("\r\n", $headers),
                'content' => json_encode($update, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'timeout' => 20,
                'ignore_errors' => true,
            ],
            'ssl' => $ssl,
        ]);

        $body = @file_get_contents($url, false, $context);

        if ($body === false) {
            $reason = error_get_last()['message'] ?? 'error desconocido';

            return [null, '', 'no se pudo entregar el update a ' . $url . ' (' . $reason . ')'];
        }

        $status = null;

        foreach ($http_response_header ?? [] as $header) {
            if (preg_match('#^HTTP/\S+\s+(\d{3})#', $header, $m) === 1) {
                $status = (int) $m[1];
            }
        }

        return [$status, $body, null];
    }

    private function stateRead(): array
    {
        if (! is_file($this->stateFile)) {
            return ['webhooks' => [], 'outbound' => [], 'pending_updates' => [], 'next_message_id' => 1, 'next_update_id' => 1];
        }

        $raw = (string) @file_get_contents($this->stateFile);
        $decoded = json_decode($raw, true);

        if (! is_array($decoded)) {
            return ['webhooks' => [], 'outbound' => [], 'pending_updates' => [], 'next_message_id' => 1, 'next_update_id' => 1];
        }

        $decoded['webhooks'] ??= [];
        $decoded['outbound'] ??= [];
        $decoded['pending_updates'] ??= [];

        return $decoded;
    }

    private function stateWrite(array $state): void
    {
        $dir = dirname($this->stateFile);

        if (! is_dir($dir)) {
            @mkdir($dir, 0777, true);
        }

        file_put_contents($this->stateFile, json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), LOCK_EX);
    }

    private function json(mixed $payload, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), "\n";
    }

    private function log(string $message): void
    {
        // OJO: bajo el SAPI `cli-server` la constante STDERR no está definida
        // (fwrite(STDERR, ...) tira "Undefined constant" y rompe el request).
        error_log('[telegram-emulador] ' . $message);
    }
}

$stateFile = getenv('TELEGRAM_EMULATOR_STATE') ?: sys_get_temp_dir() . '/telegram-emulator/state.json';
$bindHost = getenv('TELEGRAM_EMULATOR_HOST') ?: '127.0.0.1';
$bindPort = (int) (getenv('TELEGRAM_EMULATOR_PORT') ?: 8082);

(new TelegramEmulator($stateFile, $bindHost, $bindPort))->handle();
