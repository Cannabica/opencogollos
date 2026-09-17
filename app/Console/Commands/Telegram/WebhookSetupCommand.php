<?php

namespace App\Console\Commands\Telegram;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Config;
use Telegram\Bot\BotsManager;

class WebhookSetupCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'telegram:webhook:setup
                            {--bot= : Bot name from config/telegram.php (default: configured default)}
                            {--remove : Remove the webhook instead of setting it up}
                            {--info : Show current webhook information}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Setup Telegram webhook with project-specific configuration';

    /**
     * Execute the console command.
     */
    public function handle(BotsManager $telegram)
    {
        $bots = Config::get('telegram.bots', []);

        $botName = $this->option('bot') ?: Config::get('telegram.default', 'default');

        if (! isset($bots[$botName])) {
            $this->error("Bot '{$botName}' not found in configuration.");
            return 1;
        }

        $botConfig = $bots[$botName];
        $webhookUrl = $botConfig['webhook_url'] ?? null;

        if ($this->option('info')) {
            return $this->showWebhookInfo($telegram, $botName, $webhookUrl);
        }

        if ($this->option('remove')) {
            return $this->removeWebhook($telegram, $botName);
        }

        return $this->setupWebhook($telegram, $botName, $webhookUrl);
    }

    /**
     * Show current webhook information.
     */
    private function showWebhookInfo(BotsManager $telegram, string $botName, ?string $configuredUrl): int
    {
        $this->info("Webhook Information for Bot: {$botName}");
        $this->line('');

        try {
            $webhookInfo = $telegram->bot($botName)->getWebhookInfo();
            
            $this->table(
                ['Property', 'Value'],
                [
                    ['URL', $webhookInfo->getUrl() ?? 'Not set'],
                    ['Has Custom Certificate', $webhookInfo->getHasCustomCertificate() ? 'Yes' : 'No'],
                    ['Pending Update Count', $webhookInfo->getPendingUpdateCount() ?? 0],
                    ['Last Error Date', $webhookInfo->getLastErrorDate() ? date('Y-m-d H:i:s', $webhookInfo->getLastErrorDate()) : 'Never'],
                    ['Last Error Message', $webhookInfo->getLastErrorMessage() ?? 'None'],
                    ['Max Connections', $webhookInfo->getMaxConnections() ?? 'Default'],
                    ['Allowed Updates', $this->formatAllowedUpdates($webhookInfo->getAllowedUpdates())],
                ]
            );

            $this->line('');
            $this->info('Configured Webhook URL:');
            $this->line($configuredUrl ?: 'Not configured in config/telegram.php');

        } catch (\Exception $e) {
            $this->error("Failed to get webhook information: " . $e->getMessage());
            return 1;
        }

        return 0;
    }

    /**
     * Remove the webhook.
     */
    private function removeWebhook(BotsManager $telegram, string $botName): int
    {
        $this->info("Removing webhook for Bot: {$botName}");

        if ($this->confirm('Are you sure you want to remove the webhook?')) {
            try {
                $result = $telegram->bot($botName)->deleteWebhook();
                
                if ($result) {
                    $this->info('✅ Webhook removed successfully.');
                } else {
                    $this->error('❌ Failed to remove webhook.');
                    return 1;
                }
            } catch (\Exception $e) {
                $this->error("Failed to remove webhook: " . $e->getMessage());
                return 1;
            }
        } else {
            $this->info('Webhook removal cancelled.');
        }

        return 0;
    }

    /**
     * Setup the webhook.
     */
    private function setupWebhook(BotsManager $telegram, string $botName, ?string $webhookUrl): int
    {
        $this->info("Setting up webhook for Bot: {$botName}");

        if (! $webhookUrl) {
            $this->error('Webhook URL is not configured in config/telegram.php');
            $this->line('Please set the webhook_url in your telegram configuration.');
            return 1;
        }

        $this->line("Webhook URL: {$webhookUrl}");
        $this->line('');

        // Show current webhook info before setup
        try {
            $currentInfo = $telegram->bot($botName)->getWebhookInfo();
            if ($currentInfo->getUrl()) {
                $this->warn("⚠️  A webhook is already set: " . $currentInfo->getUrl());
                $this->line("Pending updates: " . ($currentInfo->getPendingUpdateCount() ?? 0));
                
                if (! $this->confirm('Do you want to replace the existing webhook?')) {
                    $this->info('Webhook setup cancelled.');
                    return 0;
                }
            }
        } catch (\Exception $e) {
            $this->warn("Could not check current webhook status: " . $e->getMessage());
        }

        // Get bot configuration
        $botConfig = Config::get("telegram.bots.{$botName}", []);
        $allowedUpdates = $botConfig['allowed_updates'] ?? ['message', 'callback_query'];
        $maxConnections = $botConfig['max_connections'] ?? 40;

        // Los DOS bots exigen secret token: Telegram lo devuelve en el header
        // X-Telegram-Bot-Api-Secret-Token de cada update, y los middlewares de cada webhook lo
        // verifican (el del tenant: VerifyTelegramTenant; el admin: AdminWebhookController).
        // OJO: el secret del tenant es NUEVO (T10.7). Si el webhook ya estaba registrado sin él,
        // hay que re-registrarlo para que Telegram empiece a mandar el header.
        $secretToken = $botName === 'admin'
            ? Config::get('telegram.admin_secret')
            : Config::get('telegram.tenant_secret');

        $this->line("Allowed Updates: " . implode(', ', $allowedUpdates));
        $this->line("Max Connections: {$maxConnections}");
        if ($secretToken) {
            $this->line('Secret Token: ✓ Set');
        } else {
            $this->line('Secret Token: ✗ Missing (el webhook de este bot rechazará TODO con 503)');
        }
        $this->line('');

        if ($this->confirm('Proceed with webhook setup?')) {
            try {
                $params = [
                    'url' => $webhookUrl,
                    'allowed_updates' => $allowedUpdates,
                    'max_connections' => $maxConnections,
                ];

                if ($secretToken) {
                    $params['secret_token'] = $secretToken;
                }

                $result = $telegram->bot($botName)->setWebhook($params);

                if ($result) {
                    $this->info('✅ Webhook setup successfully!');
                    $this->line('');
                    $this->info('Next steps:');
                    $this->line('1. Ensure your webhook URL is accessible from the internet');
                    $this->line('2. Test the webhook by sending a message to your bot');
                    $this->line('3. Use "php artisan telegram:webhook:setup --info" to check status');
                } else {
                    $this->error('❌ Failed to setup webhook.');
                    return 1;
                }
            } catch (\Exception $e) {
                $this->error("Failed to setup webhook: " . $e->getMessage());
                $this->line('');
                $this->line('Common issues:');
                $this->line('- Invalid bot token');
                $this->line('- Webhook URL not accessible');
                $this->line('- SSL certificate issues');
                return 1;
            }
        } else {
            $this->info('Webhook setup cancelled.');
        }

        return 0;
    }

    /**
     * Format allowed updates for display.
     */
    private function formatAllowedUpdates($allowedUpdates): string
    {
        if (is_array($allowedUpdates)) {
            return implode(', ', $allowedUpdates);
        }
        
        if (is_object($allowedUpdates) && method_exists($allowedUpdates, 'toArray')) {
            return implode(', ', $allowedUpdates->toArray());
        }
        
        if (is_object($allowedUpdates) && method_exists($allowedUpdates, 'all')) {
            return implode(', ', $allowedUpdates->all());
        }
        
        return 'Unknown format';
    }
}