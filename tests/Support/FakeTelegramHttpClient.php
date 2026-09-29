<?php

declare(strict_types=1);

namespace Tests\Support;

use GuzzleHttp\Promise\PromiseInterface;
use GuzzleHttp\Psr7\Response;
use Psr\Http\Message\ResponseInterface;
use Telegram\Bot\HttpClients\HttpClientInterface;

/**
 * Doble de `Telegram\Bot\HttpClients\HttpClientInterface` para tests SIN RED.
 *
 * POR QUÉ EXISTE: `Telegram\Bot\BotsManager` es `final`, así que no se puede
 * mockear con Mockery ni vía facade; y mockear `Api` sólo deja verificar que
 * algo se llamó, no QUÉ payload salió. Esta clase se inyecta como
 * `config('telegram.http_client_handler')` (el `BotsManager` la pasa al `Api`),
 * así que el `sendMessage()` real del SDK corre de punta a punta y lo único
 * simulado es el transporte.
 *
 * Devuelve respuestas canónicas del Bot API (`{"ok":true,"result":...}`) con la
 * MISMA forma que Telegram: `sendMessage` deja el objeto Message dentro de
 * `result` (el SDK arma el `MessageObject` desde el body entero, igual que en
 * producción).
 *
 * Uso típico:
 *
 *     $fake = new FakeTelegramHttpClient;
 *     config([
 *         'telegram.http_client_handler' => $fake,
 *         'telegram.base_bot_url' => 'http://emulador.test/bot',
 *         'telegram.bots.admin.token' => 'emulador-admin',
 *     ]);
 *     $this->app->instance(BotsManager::class, new BotsManager(config('telegram')));
 *
 *     app(AdminNotifierService::class)->notify('hola');
 *
 *     $this->assertSame('sendMessage', $fake->lastCall()['endpoint']);
 *     $this->assertSame('hola', $fake->lastCall()['params']['text']);
 */
final class FakeTelegramHttpClient implements HttpClientInterface
{
    /** @var list<array{url: string, token: string, http_method: string, endpoint: string, params: array<string, mixed>}> */
    private array $calls = [];

    private int $timeOut = 30;

    private int $connectTimeOut = 10;

    private int $messageId = 500;

    private ?string $failWith = null;

    /**
     * {@inheritdoc}
     */
    public function send(
        string $url,
        string $method,
        array $headers = [],
        array $options = [],
        bool $isAsyncRequest = false
    ): ResponseInterface|PromiseInterface|null {
        $endpoint = $this->endpointFromUrl($url);
        $params = $this->paramsFromOptions($options);

        $this->calls[] = [
            'url' => $url,
            'token' => $this->tokenFromUrl($url),
            'http_method' => $method,
            'endpoint' => $endpoint,
            'params' => $params,
        ];

        if ($this->failWith !== null) {
            return $this->jsonResponse([
                'ok' => false,
                'error_code' => 400,
                'description' => $this->failWith,
            ]);
        }

        return $this->jsonResponse([
            'ok' => true,
            'result' => $this->resultFor($endpoint, $params),
        ]);
    }

    public function getTimeOut(): int
    {
        return $this->timeOut;
    }

    public function setTimeOut(int $timeOut): static
    {
        $this->timeOut = $timeOut;

        return $this;
    }

    public function getConnectTimeOut(): int
    {
        return $this->connectTimeOut;
    }

    public function setConnectTimeOut(int $connectTimeOut): static
    {
        $this->connectTimeOut = $connectTimeOut;

        return $this;
    }

    // -----------------------------------------------------------------
    // API del doble
    // -----------------------------------------------------------------

    /** @return list<array{url: string, token: string, http_method: string, endpoint: string, params: array<string, mixed>}> */
    public function calls(): array
    {
        return $this->calls;
    }

    public function callCount(): int
    {
        return count($this->calls);
    }

    public function hasEndpoint(string $endpoint): bool
    {
        return $this->callsFor($endpoint) !== [];
    }

    /** @return list<array{url: string, token: string, http_method: string, endpoint: string, params: array<string, mixed>}> */
    public function callsFor(string $endpoint): array
    {
        return array_values(array_filter($this->calls, fn (array $call): bool => $call['endpoint'] === $endpoint));
    }

    /** @return array{url: string, token: string, http_method: string, endpoint: string, params: array<string, mixed>}|null */
    public function lastCall(): ?array
    {
        return $this->calls === [] ? null : $this->calls[array_key_last($this->calls)];
    }

    /** Texto del último `sendMessage`/`sendPhoto` enviado (o '' si no hubo). */
    public function lastText(): string
    {
        $last = $this->lastCall();

        if ($last === null) {
            return '';
        }

        return (string) ($last['params']['text'] ?? $last['params']['caption'] ?? '');
    }

    /** Todos los textos salientes, en orden de envío. */
    public function texts(): array
    {
        return array_values(array_map(
            fn (array $call): string => (string) ($call['params']['text'] ?? $call['params']['caption'] ?? ''),
            $this->calls
        ));
    }

    public function reset(): void
    {
        $this->calls = [];
        $this->failWith = null;
    }

    /** Hace que el próximo `send()` devuelva `{"ok":false}` (para probar los try/catch). */
    public function failWith(string $description): void
    {
        $this->failWith = $description;
    }

    // -----------------------------------------------------------------
    // Internos
    // -----------------------------------------------------------------

    private function jsonResponse(array $body): ResponseInterface
    {
        return new Response(200, ['Content-Type' => 'application/json'], (string) json_encode($body, JSON_THROW_ON_ERROR));
    }

    private function endpointFromUrl(string $url): string
    {
        $path = (string) parse_url($url, PHP_URL_PATH);

        return (string) basename($path);
    }

    private function tokenFromUrl(string $url): string
    {
        $path = trim((string) parse_url($url, PHP_URL_PATH), '/');
        $segments = explode('/', $path);

        // /bot<TOKEN>/<metodo> o /<TOKEN>/<metodo>
        if (count($segments) < 2) {
            return '';
        }

        return (string) preg_replace('/^bot/', '', $segments[count($segments) - 2]);
    }

    /**
     * El SDK normaliza los params a `form_params` (o `multipart` para archivos)
     * en `Traits\Http::normalizeParams`.
     *
     * @return array<string, mixed>
     */
    private function paramsFromOptions(array $options): array
    {
        $params = $options['form_params'] ?? $options['query'] ?? $options['json'] ?? $options['multipart'] ?? null;

        if ($params === null) {
            $body = $options['body'] ?? null;

            if (is_string($body)) {
                $decoded = json_decode($body, true);

                return is_array($decoded) ? $decoded : [];
            }

            return [];
        }

        return is_array($params) ? $this->sanitize($params) : [];
    }

    /** Los streams de archivos no se pueden comparar ni serializar. */
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

    private function resultFor(string $endpoint, array $params): mixed
    {
        $messageId = ++$this->messageId;

        $chatId = $params['chat_id'] ?? null;

        $message = [
            'message_id' => $messageId,
            'from' => [
                'id' => 987654321,
                'is_bot' => true,
                'first_name' => 'OpenCogollos Emulador',
                'username' => 'opencogollos_emulador_bot',
            ],
            'chat' => [
                'id' => is_numeric($chatId) ? (int) $chatId : 0,
                'type' => 'private',
            ],
            'date' => time(),
            'text' => (string) ($params['text'] ?? ''),
        ];

        return match ($endpoint) {
            'sendMessage', 'editMessageText' => $message,
            'sendPhoto' => array_merge($message, ['photo' => [[
                'file_id' => 'emulador-foto-' . $messageId,
                'file_unique_id' => 'emulador-' . $messageId,
                'width' => 1,
                'height' => 1,
                'file_size' => 1024,
            ]]]),
            'getMe' => [
                'id' => 987654321,
                'is_bot' => true,
                'first_name' => 'OpenCogollos Emulador',
                'username' => 'opencogollos_emulador_bot',
            ],
            'getWebhookInfo' => [
                'url' => '',
                'has_custom_certificate' => false,
                'pending_update_count' => 0,
            ],
            'getUpdates', 'getMyCommands' => [],
            default => true,
        };
    }
}
