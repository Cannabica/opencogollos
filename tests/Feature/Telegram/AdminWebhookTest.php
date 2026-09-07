<?php

namespace Tests\Feature\Telegram;

use Telegram\Bot\BotsManager;
use Tests\TestCase;

class AdminWebhookTest extends TestCase
{
    private const ADMIN_USER_ID = 812714520;
    private const SECRET='clave-local-wbhook-77';

    private function validPayload(string $text = 'hola'): array
    {
        return [
            'update_id' => 100,
            'message' => [
                'message_id' => 1,
                'date' => time(),
                'text' => $text,
                'from' => ['id' => self::ADMIN_USER_ID, 'is_bot' => false, 'first_name' => 'Frankie'],
                'chat' => ['id' => self::ADMIN_USER_ID, 'type' => 'private', 'first_name' => 'Frankie'],
            ],
        ];
    }

    /**
     * Bindea un BotsManager real con un token fake. No hay llamadas de red:
     * getWebhookUpdate() solo lee el body y commandsHandler() no dispara
     * ningún comando porque el texto no es un comando conocido.
     */
    private function bindRealBotManager(): void
    {
        config(['telegram.bots.admin.token' => '1234567890:TEST_TOKEN_SIN_USO']);
        $this->app->instance(BotsManager::class, new BotsManager(config('telegram')));
    }

    public function test_webhook_fails_closed_with_503_when_secret_is_not_configured(): void
    {
        config(['telegram.admin_secret' => '']);

        $this->postJson('/api/telegram/admin/webhook', $this->validPayload())
            ->assertStatus(503);
    }

    public function test_webhook_rejects_with_401_when_secret_header_is_missing(): void
    {
        config(['telegram.admin_secret' => self::SECRET]);

        $this->postJson('/api/telegram/admin/webhook', $this->validPayload())
            ->assertStatus(401);
    }

    public function test_webhook_rejects_with_401_when_secret_header_is_wrong(): void
    {
        config(['telegram.admin_secret' => self::SECRET]);

        $this->postJson('/api/telegram/admin/webhook', $this->validPayload(), [
            'X-Telegram-Bot-Api-Secret-Token' => 'wrong-secret',
        ])->assertStatus(401);
    }

    public function test_webhook_answers_ok_for_a_text_message_with_valid_secret(): void
    {
        config(['telegram.admin_secret' => self::SECRET]);
        $this->bindRealBotManager();

        $this->postJson('/api/telegram/admin/webhook', $this->validPayload(), [
            'X-Telegram-Bot-Api-Secret-Token' => self::SECRET,
        ])->assertOk()->assertJson(['status' => 'ok']);
    }

    public function test_webhook_answers_ok_for_a_callback_query_update(): void
    {
        config(['telegram.admin_secret' => self::SECRET]);
        $this->bindRealBotManager();

        $this->postJson('/api/telegram/admin/webhook', [
            'update_id' => 101,
            'callback_query' => [
                'id' => 'cb-1',
                'from' => ['id' => self::ADMIN_USER_ID, 'is_bot' => false, 'first_name' => 'Frankie'],
                'data' => 'whatever',
            ],
        ], [
            'X-Telegram-Bot-Api-Secret-Token' => self::SECRET,
        ])->assertOk()->assertJson(['status' => 'ok']);
    }
}
