<?php

namespace App\Console\Commands\Telegram;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Config;

class CommandsListCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'telegram:commands:list';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'List all registered Telegram bot commands';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Registered Telegram Bot Commands');
        $this->line('');

        // Get the default bot configuration
        $defaultBot = Config::get('telegram.default', 'default');
        $bots = Config::get('telegram.bots', []);
        $sharedCommands = Config::get('telegram.shared_commands', []);
        
        if (!isset($bots[$defaultBot])) {
            $this->error("Default bot '{$defaultBot}' not found in configuration.");
            return 1;
        }

        $botConfig = $bots[$defaultBot];
        $botCommands = $botConfig['commands'] ?? [];

        $this->info("Bot: {$defaultBot}");
        $this->line("Token: " . ($botConfig['token'] ? '✓ Set' : '✗ Missing'));
        $this->line("Webhook URL: " . ($botConfig['webhook_url'] ?? 'Not configured'));
        $this->line('');

        // Display registered commands for this bot
        if (empty($botCommands)) {
            $this->warn('No commands registered for this bot.');
        } else {
            $this->info('Registered Commands:');
            $this->table(
                ['Command', 'Handler Class', 'Type'],
                $this->formatCommandsList($botCommands, $sharedCommands)
            );
        }

        // Display available shared commands
        $this->line('');
        $this->info('Available Shared Commands:');
        if (empty($sharedCommands)) {
            $this->warn('No shared commands available.');
        } else {
            $this->table(
                ['Command Name', 'Handler Class'],
                $this->formatSharedCommandsList($sharedCommands)
            );
        }

        return 0;
    }

    /**
     * Format the commands list for table display.
     */
    private function formatCommandsList(array $commands, array $sharedCommands): array
    {
        $formatted = [];

        foreach ($commands as $command) {
            $type = 'Direct';
            $handler = $command;

            if (isset($sharedCommands[$command])) {
                $type = 'Shared';
                $handler = $sharedCommands[$command];
            } elseif (class_exists($command)) {
                $type = 'Class';
            }

            $formatted[] = [
                $command,
                $handler,
                $type,
            ];
        }

        return $formatted;
    }

    /**
     * Format the shared commands list for table display.
     */
    private function formatSharedCommandsList(array $sharedCommands): array
    {
        $formatted = [];

        foreach ($sharedCommands as $name => $class) {
            $formatted[] = [
                $name,
                $class,
            ];
        }

        return $formatted;
    }
}