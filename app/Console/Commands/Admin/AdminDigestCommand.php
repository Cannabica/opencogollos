<?php

namespace App\Console\Commands\Admin;

use App\Services\Admin\AdminAuthorizer;
use App\Services\Admin\AdminDigestService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Telegram\Bot\Laravel\Facades\Telegram;
use Throwable;

class AdminDigestCommand extends Command
{
    protected $signature = 'admin:digest
                            {--force : Envía el resumen aunque no haya novedades}';

    protected $description = 'Envía al superadmin por Telegram un resumen de novedades del sitio';

    public function handle(): int
    {
        if (! (bool) config('telegram.admin_digest_enabled', true)) {
            $this->info('Digest de administración deshabilitado (TELEGRAM_ADMIN_DIGEST_ENABLED=false).');
            return self::SUCCESS;
        }

        $findings = app(AdminDigestService::class)->collectFindings();

        if ($findings === [] && ! $this->option('force')) {
            $this->info('Sin novedades para reportar.');
            return self::SUCCESS;
        }

        $chatIds = app(AdminAuthorizer::class)->allowedUserIds();

        if ($chatIds === []) {
            $this->error('No hay ids de Telegram autorizados (TELEGRAM_ADMIN_ALLOWED_USER_IDS).');
            return self::FAILURE;
        }

        $text = "🔔 <b>Resumen de la plataforma</b>\n\n" . implode("\n", $findings ?: ['Todo en orden ✅']);

        $sent = 0;
        foreach ($chatIds as $chatId) {
            try {
                Telegram::bot('admin')->sendMessage([
                    'chat_id' => $chatId,
                    'text' => $text,
                    'parse_mode' => 'HTML',
                ]);
                $sent++;
            } catch (Throwable $e) {
                Log::error('Admin digest: fallo al enviar resumen', [
                    'chat_id' => $chatId,
                    'error' => $e->getMessage(),
                ]);
                $this->error("Fallo al enviar a {$chatId}: " . $e->getMessage());
            }
        }

        $this->info("Resumen enviado a {$sent} chat(s).");

        return $sent > 0 ? self::SUCCESS : self::FAILURE;
    }
}
