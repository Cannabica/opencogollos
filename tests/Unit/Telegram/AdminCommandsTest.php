<?php

namespace Tests\Unit\Telegram;

use App\Telegram\Admin\Commands\StartCommand;
use Mockery;
use Telegram\Bot\Api;
use Telegram\Bot\Objects\Message;
use Telegram\Bot\Objects\Update;
use Tests\TestCase;

class AdminCommandsTest extends TestCase
{
    private const ADMIN_USER_ID = 812714520;

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    private function runCommand(Api $api, string $text, int $fromId): array
    {
        $update = new Update([
            'update_id' => 100,
            'message' => [
                'message_id' => 1,
                'date' => time(),
                'text' => $text,
                'from' => ['id' => $fromId, 'is_bot' => false, 'first_name' => 'Tester'],
                'chat' => ['id' => $fromId, 'type' => 'private', 'first_name' => 'Tester'],
            ],
        ]);

        $command = new StartCommand();
        $command->make($api, $update, ['offset' => 0, 'length' => strlen($text), 'type' => 'bot_command']);

        return $this->replies;
    }

    private array $replies = [];

    private function apiMock(): Api
    {
        $api = Mockery::mock(Api::class);
        $api->shouldReceive('sendMessage')->andReturnUsing(function (array $params): Message {
            $this->replies[] = $params;
            return new Message(['message_id' => 1, 'chat' => ['id' => $params['chat_id'] ?? 0, 'type' => 'private']]);
        });

        return $api;
    }

    public function test_start_command_replies_welcome_to_an_authorized_admin(): void
    {
        config(['telegram.admin_allowed_user_ids' => (string) self::ADMIN_USER_ID]);

        $this->runCommand($this->apiMock(), '/start', self::ADMIN_USER_ID);

        $this->assertCount(1, $this->replies);
        $this->assertSame(self::ADMIN_USER_ID, $this->replies[0]['chat_id']);
        $this->assertStringContainsString('bot de administración', $this->replies[0]['text']);
    }

    public function test_start_command_rejects_an_unauthorized_user(): void
    {
        config(['telegram.admin_allowed_user_ids' => (string) self::ADMIN_USER_ID]);

        $this->runCommand($this->apiMock(), '/start', 999999);

        $this->assertCount(1, $this->replies);
        $this->assertStringContainsString('No estás autorizado', $this->replies[0]['text']);
    }
}
